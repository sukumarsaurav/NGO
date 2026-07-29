<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('slug', 120)->unique();
            $table->smallInteger('rank')->default(0);
            // FK to document_templates added in M04's migration (Sprint 4) —
            // that table doesn't exist yet. Nullable, unconstrained until then.
            // See docs/02-DATABASE-SCHEMA.md §12 (circular-dependency note).
            $table->unsignedBigInteger('letter_template_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designations');
    }
};
