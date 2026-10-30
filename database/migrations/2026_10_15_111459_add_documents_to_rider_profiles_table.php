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
        Schema::table('rider_profiles', function (Blueprint $table) {
            $table->string('license_photo_path')->nullable()->after('license_number');
            $table->string('or_cr_photo_path')->nullable()->after('license_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rider_profiles', function (Blueprint $table) {
            $table->dropColumn(['license_photo_path', 'or_cr_photo_path']);
        });
    }
};
