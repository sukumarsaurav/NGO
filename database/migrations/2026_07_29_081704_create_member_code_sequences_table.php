<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The row-locked sequence backing MemberCodeGenerator — same pattern as
 * receipt_sequences (M07), though the stakes are much lower here. One row
 * per calendar year. See docs/modules/M03-members.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_code_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_code_sequences');
    }
};
