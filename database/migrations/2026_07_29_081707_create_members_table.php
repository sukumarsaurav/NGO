<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->string('member_code', 30)->unique()->comment('VGWGF-2026-00123, row-locked generator');
            $table->foreignId('department_id')->nullable()
                ->constrained('departments')->restrictOnDelete();
            $table->foreignId('designation_id')->nullable()
                ->constrained('designations')->restrictOnDelete();
            $table->string('photo_path')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('address_line1', 190)->nullable();
            $table->string('address_line2', 190)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('emergency_contact_name', 120)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('id_proof_type', 40)->nullable();
            // ENCRYPTED cast — 512 chars to hold the ciphertext envelope, not
            // the ~12-char plaintext. See mysql-schema.sql IMPLEMENTATION NOTE 1:
            // a narrow column here silently truncates ciphertext into garbage.
            $table->string('id_proof_number', 512)->nullable();
            $table->date('joined_on');
            $table->date('valid_until')->nullable()->comment('ID card expiry');
            $table->enum('status', ['pending', 'active', 'suspended', 'resigned', 'expired'])
                ->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'department_id']);
            $table->index('valid_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
