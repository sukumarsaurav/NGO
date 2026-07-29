<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            // Nested departments; cycles are validated in the model/action layer,
            // not the schema — MySQL/SQLite can't express "no cycles" declaratively.
            $table->foreignId('parent_id')->nullable()
                ->constrained('departments')->restrictOnDelete();
            // Drives /manager panel scoping — see docs/modules/M11-manager-panel.md.
            $table->foreignId('manager_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
