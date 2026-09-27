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
        Schema::table('doctors', function (Blueprint $table) {
            // Contact details for admin use — independent of `user_id`,
            // which links a doctor's clinical profile to a login account
            // (a separate, optional concern).
            $table->string('email')->nullable()->after('specialty_en');
            $table->string('phone')->nullable()->after('email');
            // Soft "delete" strategy: an inactive doctor drops off the
            // public site without destroying their appointment history.
            $table->boolean('is_active')->default(true)->after('featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone', 'is_active']);
        });
    }
};
