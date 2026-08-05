<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('csr_inquiries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('organisation_name', 190);
            $table->string('contact_name', 150);
            $table->string('email', 190);
            $table->string('phone', 20);
            $table->text('message');
            $table->enum('status', ['new', 'contacted', 'closed'])->default('new');
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
        Schema::dropIfExists('csr_inquiries');
    }
};
