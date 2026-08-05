<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 150);
            $table->string('email', 190);
            $table->string('phone', 20);
            // Free-text field of interest — same reasoning as partners.category.
            $table->string('track', 100)->nullable();
            $table->text('message')->nullable();
            // `local` disk, deliberately — resumes carry PII and must never be
            // publicly linkable, unlike certificates.file_path.
            $table->string('resume_path')->nullable();
            $table->enum('status', ['new', 'reviewed', 'closed'])->default('new');
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_applications');
    }
};
