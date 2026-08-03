<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_updates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('title', 190);
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->timestamp('published_at')->nullable();
            // Wired to an actual email send in Sprint 10 (M08's "Campaign
            // update → notify donors flow") — this sprint only records intent.
            $table->boolean('notify_donors')->default(false);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['campaign_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_updates');
    }
};
