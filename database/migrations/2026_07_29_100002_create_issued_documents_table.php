<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issued_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('document_number', 40)->unique();
            $table->enum('type', ['id_card', 'appointment_letter', 'certificate'])->index();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('document_templates');
            $table->string('title', 190);
            $table->json('snapshot_data');
            $table->string('file_path', 255)->nullable();
            $table->string('qr_payload', 255);
            $table->foreignId('issued_by_user_id')->constrained('users');
            $table->date('issued_on');
            $table->date('valid_until')->nullable();
            $table->enum('status', ['queued', 'issued', 'revoked', 'superseded'])->default('queued')->index();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason', 255)->nullable();
            $table->foreignId('superseded_by_id')->nullable()->constrained('issued_documents')->nullOnDelete();
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('verified_count')->default(0);
            $table->timestamps();

            $table->index(['member_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_documents');
    }
};
