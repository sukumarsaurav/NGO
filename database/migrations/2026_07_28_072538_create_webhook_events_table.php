<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotency guard. EVERY gateway webhook writes here first — the unique key on
 * (provider, event_id) is what makes replays safe. See docs/modules/M05-donations-payments.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->default('razorpay');
            $table->string('event_id', 190);
            $table->string('event_type', 80);
            $table->json('payload');
            $table->enum('status', ['received', 'processed', 'failed', 'ignored'])->default('received');
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
            $table->index(['status', 'created_at']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
