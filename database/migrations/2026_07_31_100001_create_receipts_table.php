<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('receipt_number', 50)->unique();
            $table->unsignedInteger('sequence_number');
            $table->enum('series', ['donation', '80g'])->index();
            $table->unsignedSmallInteger('revision')->default(1);
            $table->foreignId('donation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('donor_id')->constrained()->cascadeOnDelete();
            $table->char('financial_year', 7)->index();
            $table->unsignedBigInteger('amount');
            $table->string('amount_in_words', 255);
            $table->json('snapshot_data');
            $table->string('file_path', 255)->nullable();
            $table->date('issued_on');
            $table->timestamp('emailed_at')->nullable();
            $table->enum('email_status', ['pending', 'sent', 'failed', 'bounced'])->default('pending');
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_cancelled')->default(false);
            $table->string('cancelled_reason', 255)->nullable();
            $table->timestamps();

            // At most one *non-cancelled* receipt per (donation, series) is an
            // application invariant (guarded write in GenerateDonationReceipt),
            // not a DB constraint — see docs/02-DATABASE-SCHEMA.md's note on why
            // UNIQUE(donation_id, series) alone would break reissue-after-cancel.
            $table->unique(['donation_id', 'series', 'revision']);
            $table->index(['series', 'financial_year', 'sequence_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
