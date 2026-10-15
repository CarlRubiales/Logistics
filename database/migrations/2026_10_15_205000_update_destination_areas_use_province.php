<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destination_areas', function (Blueprint $table) {
            $table->string('province')->nullable()->after('name');
        });

        // Drop the unique constraint on (store_id, code) so code conflicts never occur
        try {
            Schema::table('destination_areas', function (Blueprint $table) {
                $table->dropUnique('destination_areas_store_id_code_unique');
            });
        } catch (\Throwable $e) {
            // Index might already be dropped or named differently
        }

        // Make code nullable
        try {
            Schema::table('destination_areas', function (Blueprint $table) {
                $table->string('code')->nullable()->change();
            });
        } catch (\Throwable $e) {
            // In case driver does not support change without doctrine
        }

        // Backfill existing rows with 'Laguna' if province is empty
        DB::table('destination_areas')->whereNull('province')->orWhere('province', '')->update([
            'province' => 'Laguna',
        ]);
    }

    public function down(): void
    {
        Schema::table('destination_areas', function (Blueprint $table) {
            $table->dropColumn('province');
        });
    }
};

