<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30)->default('razorpay');
            $table->string('provider_order_id', 120)->nullable()->index();
            $table->string('provider_payment_id', 120)->nullable()->unique();
            $table->string('provider_signature', 255)->nullable();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('fee')->default(0);
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('net_amount')->default(0);
            $table->enum('status', ['created', 'authorized', 'captured', 'failed', 'refunded'])->index();
            $table->string('method', 30)->nullable();
            $table->string('bank', 60)->nullable();
            $table->string('vpa', 120)->nullable();
            $table->char('card_last4', 4)->nullable();
            $table->string('error_code', 60)->nullable();
            $table->text('error_description')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->unsignedBigInteger('refund_amount')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
