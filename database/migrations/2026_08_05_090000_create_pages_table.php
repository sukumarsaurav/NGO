<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 190)->unique();
            $table->string('title', 190);
            $table->longText('body');
            $table->string('template', 50)->default('default');
            $table->string('meta_title', 190)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->boolean('is_published')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
