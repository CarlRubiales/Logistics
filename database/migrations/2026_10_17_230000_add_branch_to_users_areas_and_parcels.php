<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'branch')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('branch')->nullable()->after('role');
            });
        }

        if (Schema::hasTable('destination_areas') && ! Schema::hasColumn('destination_areas', 'branch')) {
            Schema::table('destination_areas', function (Blueprint $table) {
                $table->string('branch')->nullable()->after('name');
            });
        }

        if (Schema::hasTable('parcels') && ! Schema::hasColumn('parcels', 'branch')) {
            Schema::table('parcels', function (Blueprint $table) {
                $table->string('branch')->nullable()->after('destination_area_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'branch')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('branch');
            });
        }

        if (Schema::hasTable('destination_areas') && Schema::hasColumn('destination_areas', 'branch')) {
            Schema::table('destination_areas', function (Blueprint $table) {
                $table->dropColumn('branch');
            });
        }

        if (Schema::hasTable('parcels') && Schema::hasColumn('parcels', 'branch')) {
            Schema::table('parcels', function (Blueprint $table) {
                $table->dropColumn('branch');
            });
        }
    }
};

