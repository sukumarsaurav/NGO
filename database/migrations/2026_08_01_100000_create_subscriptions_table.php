<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('donor_id')->constrained()->cascadeOnDelete();
            // campaign_id: FK deferred until M08 exists — same forward-reference
            // pattern as donations.campaign_id.
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->string('provider', 30)->default('razorpay');
            $table->string('provider_plan_id', 120)->nullable();
            $table->string('provider_subscription_id', 120)->nullable()->unique();
            $table->string('provider_token_id', 120)->nullable();
            $table->unsignedBigInteger('amount');
            $table->enum('interval', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->integer('total_cycles')->nullable();
            $table->integer('completed_cycles')->default(0);
            $table->enum('status', [
                'created', 'pending_authentication', 'active', 'paused',
                'halted', 'completed', 'cancelled', 'expired',
            ])->default('created')->index();
            $table->enum('mandate_type', ['upi_autopay', 'emandate', 'card'])->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('next_charge_at')->nullable()->index();
            $table->timestamp('last_charged_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->enum('cancelled_by', ['donor', 'admin', 'gateway', 'bank'])->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->smallInteger('failed_charge_count')->default(0);
            $table->unsignedBigInteger('total_collected')->default(0);
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
