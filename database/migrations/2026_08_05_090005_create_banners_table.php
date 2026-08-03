<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 190);
            $table->string('subtitle', 255)->nullable();
            $table->string('image_path');
            // Falls back to image_path with object-fit: cover — a 1920x720
            // desktop image letterboxes to an unreadable strip at 360px.
            $table->string('mobile_image_path')->nullable();
            $table->string('cta_label', 60)->nullable();
            $table->string('cta_url')->nullable();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
