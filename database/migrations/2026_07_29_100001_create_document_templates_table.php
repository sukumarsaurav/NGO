<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->enum('type', ['id_card', 'appointment_letter', 'certificate'])->index();
            $table->longText('body_html');
            $table->longText('css')->nullable();
            $table->string('page_size', 20)->default('A4');
            $table->enum('orientation', ['portrait', 'landscape'])->default('portrait');
            $table->string('background_path', 255)->nullable();
            $table->boolean('qr_enabled')->default(true);
            $table->json('qr_position')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Deferred from designations' own migration (Sprint 3) — see that
        // file's comment. document_templates now exists, so the FK can bind.
        Schema::table('designations', function (Blueprint $table) {
            $table->foreign('letter_template_id')->references('id')->on('document_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropForeign(['letter_template_id']);
        });

        Schema::dropIfExists('document_templates');
    }
};
