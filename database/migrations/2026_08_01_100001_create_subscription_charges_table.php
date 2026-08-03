<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donation_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('cycle_number');
            $table->unsignedBigInteger('amount');
            $table->enum('status', ['scheduled', 'processing', 'succeeded', 'failed', 'skipped'])->index();
            $table->string('provider_payment_id', 120)->nullable();
            $table->timestamp('scheduled_for');
            $table->timestamp('charged_at')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->smallInteger('retry_count')->default(0);
            $table->timestamps();

            $table->unique(['subscription_id', 'cycle_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_charges');
    }
};
