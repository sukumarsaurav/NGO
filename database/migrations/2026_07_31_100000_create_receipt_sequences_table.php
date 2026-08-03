<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The concurrency-critical table. One row per (series, financial year),
 * locked with `SELECT ... FOR UPDATE` when allocating a number — see
 * ReceiptNumberGenerator and docs/02-DATABASE-SCHEMA.md §"receipt_sequences".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_sequences', function (Blueprint $table) {
            $table->id();
            $table->enum('series', ['donation', '80g']);
            $table->char('financial_year', 7);
            $table->string('prefix', 20);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['series', 'financial_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_sequences');
    }
};
