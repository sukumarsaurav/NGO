<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donation_id')->constrained()->restrictOnDelete();
            $table->foreignId('campaign_product_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            // Snapshotted at donation time — prices change; a receipt
            // reprinted next year must show what the donor actually paid.
            // See docs/02-DATABASE-SCHEMA.md §8.
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();

            $table->index('donation_id');
            $table->index('campaign_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_items');
    }
};
