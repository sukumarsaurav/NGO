<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('donation_number', 40)->unique()->nullable();
            $table->foreignId('donor_id')->constrained()->cascadeOnDelete();
            // campaign_id / subscription_id: FK deferred until M06/M08 exist —
            // same forward-reference pattern as designations.letter_template_id.
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('items_amount')->default(0);
            $table->unsignedBigInteger('free_amount')->default(0);
            $table->char('currency', 3)->default('INR');
            $table->enum('type', ['one_time', 'recurring'])->default('one_time');
            $table->enum('payment_mode', ['upi', 'card', 'netbanking', 'wallet', 'cash', 'cheque', 'bank_transfer', 'other'])->nullable();
            $table->enum('status', ['pending', 'processing', 'succeeded', 'failed', 'refunded', 'cancelled', 'abandoned'])->default('pending')->index();
            $table->boolean('is_offline')->default(false);
            $table->timestamp('donated_at')->nullable();
            $table->char('financial_year', 7)->nullable()->index();
            $table->boolean('eligible_for_80g')->default(true);
            $table->text('message')->nullable();
            $table->string('dedicated_to', 150)->nullable();
            $table->string('source', 50)->nullable();
            $table->json('utm_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'donated_at']);
            $table->index(['campaign_id', 'status']);
            $table->index(['financial_year', 'status']);
            $table->index(['donor_id', 'donated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
