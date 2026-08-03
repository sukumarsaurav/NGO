<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('press_mentions', function (Blueprint $table) {
            $table->id();
            $table->string('outlet_name', 150);
            $table->string('logo_path')->nullable();
            $table->string('url')->nullable();
            $table->date('published_on')->nullable();
            $table->boolean('is_published')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('press_mentions');
    }
};
