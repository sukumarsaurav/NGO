<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 190);
            $table->string('logo_path');
            $table->string('website_url')->nullable();
            // Free-text, not an enum — the client hasn't specified a fixed
            // taxonomy yet (e.g. "Corporate Partner", "Implementation Partner").
            $table->string('category', 100)->nullable();
            $table->boolean('is_published')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
