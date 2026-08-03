<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->string('icon_path')->nullable();
            $table->text('description')->nullable();
            // 150+ words of unique copy for /causes/{slug} — see docs/07-SEO.md §1.
            $table->text('intro_body')->nullable();
            $table->string('meta_title', 190)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_categories');
    }
};
