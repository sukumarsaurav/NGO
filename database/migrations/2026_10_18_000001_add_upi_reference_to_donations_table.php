<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Direct UPI payments never pass through the payment gateway, so there is no
 * `payment_transactions` row to carry the bank's transaction reference. The donor reports
 * the 12-digit UPI reference (UTR) after paying and an admin matches it against the bank
 * statement before confirming.
 *
 * UNIQUE: one bank transaction can only ever back one donation. Without it the same UTR
 * could be submitted against several pending donations and an admin might confirm more than
 * was actually paid. NULLs are exempt, so donations that never used UPI are unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('upi_reference', 40)->nullable()->unique()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropUnique(['upi_reference']);
            $table->dropColumn('upi_reference');
        });
    }
};
