<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_products', function (Blueprint $table) {
            $table->id();
            // restrict, not cascade — donation_items restricts on this table,
            // so a cascade from campaigns would fail at the DB level anyway.
            // Campaigns are soft-deleted, never hard-deleted. See
            // docs/02-DATABASE-SCHEMA.md §8.
            $table->foreignId('campaign_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('units_needed');
            $table->unsignedInteger('units_funded')->default(0);
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['campaign_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_products');
    }
};
