<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_faqs', function (Blueprint $table) {
            $table->id();
            // Nullable — a null campaign_id means a global FAQ shown on every
            // campaign page. See docs/02-DATABASE-SCHEMA.md §8.
            $table->foreignId('campaign_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('question', 255);
            $table->text('answer');
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_faqs');
    }
};
