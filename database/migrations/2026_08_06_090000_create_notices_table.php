<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('title', 190);
            $table->longText('body');
            $table->string('attachment_path')->nullable();
            $table->enum('audience', ['all_members', 'department', 'designation', 'specific', 'all_donors'])->index();
            $table->json('audience_filter')->nullable();
            $table->enum('priority', ['normal', 'important', 'urgent'])->default('normal');
            $table->boolean('send_email')->default(true);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed'])->default('draft')->index();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('read_count')->default(0);
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
