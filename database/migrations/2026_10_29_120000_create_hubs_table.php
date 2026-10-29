<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // e.g. "Sta Cruz Hub"
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add hub_id to rider_profiles
        Schema::table('rider_profiles', function (Blueprint $table) {
            $table->foreignId('hub_id')->nullable()->constrained('hubs')->nullOnDelete()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('rider_profiles', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Hub::class);
            $table->dropColumn('hub_id');
        });
        Schema::dropIfExists('hubs');
    }
};
