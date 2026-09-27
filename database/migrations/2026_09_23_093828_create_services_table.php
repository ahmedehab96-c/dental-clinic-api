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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('icon');
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('short_description_ar');
            $table->string('short_description_en');
            $table->text('description_ar');
            $table->text('description_en');
            $table->string('image_path');
            $table->string('duration_ar');
            $table->string('duration_en');
            $table->unsignedInteger('price_from');
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
