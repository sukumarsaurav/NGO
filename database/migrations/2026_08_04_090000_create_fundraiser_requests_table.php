<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fundraiser_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->string('email', 190);
            $table->string('phone', 20);
            $table->string('organisation_name', 190)->nullable();
            $table->foreignId('cause_category_id')->nullable()->constrained('campaign_categories')->nullOnDelete();
            $table->string('title', 190);
            $table->text('description');
            $table->unsignedBigInteger('goal_amount');
            // Uploaded proof paths — registration certificate, 80G, photos.
            $table->json('documents')->nullable();
            $table->enum('status', ['new', 'under_review', 'approved', 'rejected'])->default('new');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            // Set on approval — the draft campaign created from this request.
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fundraiser_requests');
    }
};
