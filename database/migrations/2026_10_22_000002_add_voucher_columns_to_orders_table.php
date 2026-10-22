<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'voucher_code')) {
                $table->string('voucher_code', 50)->nullable();
            }
            if (! Schema::hasColumn('orders', 'voucher_type')) {
                $table->string('voucher_type', 20)->nullable();
            }
            if (! Schema::hasColumn('orders', 'voucher_discount')) {
                $table->decimal('voucher_discount', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('orders', 'shipping_discount')) {
                $table->decimal('shipping_discount', 10, 2)->default(0);
            }
        });

        // Fixed-amount vouchers are no longer supported.
        if (Schema::hasTable('vouchers')) {
            \Illuminate\Support\Facades\DB::table('vouchers')->where('type', 'fixed')->delete();
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['voucher_code', 'voucher_type', 'voucher_discount', 'shipping_discount'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
