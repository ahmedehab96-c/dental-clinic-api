<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('category_id')->constrained('blog_categories')->cascadeOnDelete();
            $table->foreignId('author_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('title_ar');
            $table->string('title_en');
            $table->string('excerpt_ar');
            $table->string('excerpt_en');
            $table->json('content_ar');
            $table->json('content_en');
            $table->string('image_path');
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('read_minutes')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
