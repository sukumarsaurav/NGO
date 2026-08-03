<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 190)->unique();
            $table->string('title', 190);
            $table->string('excerpt', 500)->nullable();
            $table->longText('body');
            $table->string('cover_image_path')->nullable();
            // Cast to the PostCategory enum — free text breaks the blog
            // filter. See docs/06-UI-UX-FOUNDATION.md §7.
            $table->string('category', 80)->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('author_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('view_count')->default(0);
            $table->string('meta_title', 190)->nullable();
            $table->string('meta_description', 255)->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
