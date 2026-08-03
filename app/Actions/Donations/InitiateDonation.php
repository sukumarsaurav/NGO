<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\Donation;
use App\Models\Donor;
use App\Services\Payment\DTOs\InitiateDonationResult;
use App\Services\Payment\DTOs\PaymentIntent;
use App\Services\Payment\PaymentGateway;
use App\Services\Settings\SettingsRepository;
use App\Support\FinancialYear;
use App\Support\Money;
use InvalidArgumentException;

/**
 * Step 3 of the online donation flow — see docs/modules/M05-donations-payments.md.
 * Resolves/creates the Donor, creates the Donation (status: pending), asks
 * the gateway for an order, and records the first PaymentTransaction attempt.
 *
 * The amount here must already be server-validated Money — this action
 * trusts its caller on that point, but re-checks the settings floor itself
 * as a second line of defence (see "server-side amount validation").
 *
 * `$amount` is the free-form (non-catalogue) portion of the gift. `$items`
 * carries the needs-catalogue selection as `[{product_id, quantity}, ...]` —
 * see docs/modules/M08-campaigns-crowdfunding.md's "The money rules".
 * **Quantities come from the client. Money never does**: `unit_price` is
 * re-read from `campaign_products` here, never trusted from the request, so
 * a tampered price or line total is simply ignored.
 */
final class InitiateDonation
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly SettingsRepository $settings,
    ) {}

    /**
     * @param  array<string, string>  $utmData
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    public function handle(
        string $donorName,
        string $donorEmail,
        ?string $donorPhone,
        Money $amount,
        ?int $campaignId = null,
        bool $isAnonymous = false,
        ?string $message = null,
        ?string $dedicatedTo = null,
        ?string $source = null,
        array $utmData = [],
        ?string $ipAddress = null,
        array $items = [],
    ): InitiateDonationResult {
        if ($items !== [] && $campaignId === null) {
            throw new InvalidArgumentException('Catalogue items require a campaign.');
        }

        $campaign = null;

        if ($campaignId !== null) {
            $campaign = Campaign::query()->find($campaignId);

            // The campaign may have closed, paused or been unpublished between
            // the donor loading the page and submitting — see M08's "Donation
            // to a paused campaign" edge case. Never trust the client's belief
            // that a campaign is still open.
            if (! $campaign || ! $campaign->status->acceptsDonations()) {
                throw new InvalidArgumentException('This campaign is not currently accepting donations.');
            }
        }

        [$lineItems, $droppedProductNames] = $this->resolveLineItems($campaignId, $items);

        $itemsAmount = array_sum(array_column($lineItems, 'line_total'));
        $freeAmount = $amount->toPaise();
        $totalPaise = $itemsAmount + $freeAmount;

        $minAmount = (int) $this->settings->get('donation.min_amount', 0);

        if ($totalPaise < $minAmount) {
            throw new InvalidArgumentException(
                'Amount must be at least '.Money::fromPaise($minAmount)->toRupees().' rupees.'
            );
        }

        $donor = $this->resolveDonor($donorName, $donorEmail, $donorPhone, $isAnonymous);

        $donation = Donation::query()->create([
            'donor_id' => $donor->id,
            'campaign_id' => $campaignId,
            'amount' => $totalPaise,
            'items_amount' => $itemsAmount,
            'free_amount' => $freeAmount,
            'currency' => $this->settings->get('donation.currency', 'INR'),
            'type' => 'one_time',
            'status' => 'pending',
            'is_offline' => false,
            'financial_year' => FinancialYear::current()->toString(),
            'eligible_for_80g' => true,
            'message' => $message,
            'dedicated_to' => $dedicatedTo,
            'source' => $source,
            'utm_data' => $utmData,
            'ip_address' => $ipAddress,
        ]);

        foreach ($lineItems as $lineItem) {
            $donation->items()->create($lineItem);
        }

        $totalMoney = Money::fromPaise($totalPaise);

        $order = $this->gateway->createOrder(new PaymentIntent(
            receipt: (string) $donation->uuid,
            amount: $totalMoney,
            currency: $donation->currency,
            donorEmail: $donorEmail,
            donorName: $donorName,
            donorPhone: $donorPhone,
            notes: ['donation_uuid' => (string) $donation->uuid],
        ));

        $donation->transactions()->create([
            'provider' => 'razorpay',
            'provider_order_id' => $order->orderId,
            'amount' => $totalPaise,
            'status' => 'created',
        ]);

        return new InitiateDonationResult($donation, $order, $droppedProductNames);
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     * @return array{0: list<array{campaign_product_id: int, quantity: int, unit_price: int, line_total: int}>, 1: list<string>}
     */
    private function resolveLineItems(?int $campaignId, array $items): array
    {
        if ($items === []) {
            return [[], []];
        }

        $productIds = array_column($items, 'product_id');

        $products = CampaignProduct::query()
            ->where('campaign_id', $campaignId)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $lineItems = [];
        $dropped = [];

        foreach ($items as $item) {
            $quantity = (int) $item['quantity'];

            if ($quantity < 1) {
                continue;
            }

            /** @var CampaignProduct|null $product */
            $product = $products->get($item['product_id']);

            // Product deactivated (or never existed / belongs to a different
            // campaign — request tampering) between page load and submit:
            // drop the line, keep the rest, never silently recalculate a
            // total the donor has already seen without telling them.
            if (! $product || ! $product->is_active) {
                if ($product) {
                    $dropped[] = $product->name;
                }

                continue;
            }

            $lineItems[] = [
                'campaign_product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->unit_price,
                'line_total' => $product->unit_price * $quantity,
            ];
        }

        return [$lineItems, $dropped];
    }

    private function resolveDonor(string $name, string $email, ?string $phone, bool $isAnonymous): Donor
    {
        $donor = Donor::findByEmail($email);

        if ($donor) {
            // Keep the most recent name; see "same email, different name" in
            // docs/modules/M05-donations-payments.md.
            $donor->update([
                'name' => $name,
                'phone' => $phone ?? $donor->phone,
                'is_anonymous' => $isAnonymous,
            ]);

            return $donor->fresh();
        }

        return Donor::query()->create([
            'name' => $name,
            'email' => trim(mb_strtolower($email)),
            'phone' => $phone,
            'is_anonymous' => $isAnonymous,
        ]);
    }
}
