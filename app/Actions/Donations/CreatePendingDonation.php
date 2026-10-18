<?php

declare(strict_types=1);

namespace App\Actions\Donations;

use App\Models\Campaign;
use App\Models\CampaignProduct;
use App\Models\Donation;
use App\Models\Donor;
use App\Services\Settings\SettingsRepository;
use App\Support\FinancialYear;
use App\Support\Money;
use InvalidArgumentException;

/**
 * Validates a prospective gift and creates the pending Donation with its line items —
 * everything that happens before money is asked for, and nothing that needs a payment
 * gateway.
 *
 * Shared by the gateway flow (InitiateDonation, which then asks the gateway for an order)
 * and the direct-UPI flow (InitiateUpiDonation, which does not). Keeping it gateway-free
 * means a UPI donation can be created in production before Razorpay keys exist, without
 * constructing a Razorpay client just to throw it away.
 *
 * `$amount` is the free-form (non-catalogue) portion of the gift. `$items` carries the
 * needs-catalogue selection as `[{product_id, quantity}, ...]`. **Quantities come from the
 * client. Money never does**: `unit_price` is re-read from `campaign_products` here, never
 * trusted from the request.
 */
final class CreatePendingDonation
{
    public function __construct(
        private readonly SettingsRepository $settings,
    ) {}

    /**
     * @param  array<string, string>  $utmData
     * @param  list<array{product_id: int, quantity: int}>  $items
     * @return array{0: Donation, 1: list<string>} the donation and the names of any dropped products
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
    ): array {
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

        return [$donation, $droppedProductNames];
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
