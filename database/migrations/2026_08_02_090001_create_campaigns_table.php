<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 190)->unique();
            $table->foreignId('category_id')->constrained('campaign_categories')->restrictOnDelete();
            $table->string('title', 190);
            $table->string('subtitle', 255)->nullable();
            $table->string('beneficiary_name', 150)->nullable();
            $table->longText('story');
            $table->string('cover_image_path')->nullable();
            // Required whenever cover_image_path is set — see docs/07-SEO.md
            // §4: "Every image carries meaningful alt text."
            $table->string('cover_image_alt', 255)->nullable();
            $table->string('video_url')->nullable();
            $table->unsignedBigInteger('goal_amount');
            $table->unsignedBigInteger('raised_amount')->default(0);
            $table->unsignedInteger('donor_count')->default(0);
            $table->unsignedBigInteger('offline_raised_amount')->default(0);
            $table->boolean('allows_recurring')->default(true);
            $table->boolean('is_tax_benefit')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_urgent')->default(false);
            $table->enum('status', ['draft', 'pending_review', 'active', 'paused', 'completed', 'closed'])->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->string('meta_title', 190)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('is_featured');
            $table->index('ends_at');
            $table->index(['status', 'is_featured', 'sort_order']);
            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
