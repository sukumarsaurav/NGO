<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donors', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('name', 150);
            $table->string('email', 190)->index();
            $table->string('phone', 20)->nullable()->index();
            // 512, not the 20 chars a plaintext PAN needs — this column holds
            // Laravel's `encrypted` cast ciphertext, same widening as
            // members.id_proof_number.
            $table->string('pan', 512)->nullable();
            $table->string('address_line1', 190)->nullable();
            $table->string('address_line2', 190)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('state', 80)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('country', 60)->default('India');
            $table->enum('donor_type', ['individual', 'company', 'trust', 'huf', 'foreign'])->default('individual');
            $table->unsignedBigInteger('total_donated')->default(0);
            $table->unsignedInteger('donation_count')->default(0);
            $table->timestamp('first_donated_at')->nullable();
            $table->timestamp('last_donated_at')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('marketing_opt_in')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donors');
    }
};
