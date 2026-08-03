<?php

declare(strict_types=1);

use App\Models\DocumentNumberSequence;
use App\Models\DonationItem;
use App\Models\MemberCodeSequence;
use App\Models\NoticeRecipient;
use App\Models\PaymentTransaction;
use App\Models\ReceiptSequence;
use App\Models\SubscriptionCharge;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * "A registration test is cheaper than vigilance" — docs/modules/M11-manager-panel.md.
 * Every model reachable through a Filament resource must have a policy;
 * models never directly authorized (sequence/pivot/internal bookkeeping
 * rows with no resource of their own) are the only exemptions.
 */
it('registers a policy for every model that has its own Filament resource', function () {
    $exempt = [
        NoticeRecipient::class,
        DocumentNumberSequence::class,
        MemberCodeSequence::class,
        ReceiptSequence::class,
        WebhookEvent::class,
        PaymentTransaction::class,
        DonationItem::class,
        SubscriptionCharge::class,
    ];

    $modelFiles = glob(app_path('Models/*.php'));
    $missing = [];

    foreach ($modelFiles as $file) {
        $class = 'App\\Models\\'.Str::before(basename($file), '.php');

        if (in_array($class, $exempt, true)) {
            continue;
        }

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            continue;
        }

        if (Gate::getPolicyFor($class) === null) {
            $missing[] = $class;
        }
    }

    expect($missing)->toBe([]);
});
