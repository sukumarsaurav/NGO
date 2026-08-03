<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Row-locked sequence backing DocumentNumberGenerator — same pattern as
 * member_code_sequences and receipt_sequences. One row per (type, year),
 * since each document type gets its own prefix and counter
 * (e.g. `AL-2026-0042` for appointment letters).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['id_card', 'appointment_letter', 'certificate']);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_number_sequences');
    }
};
