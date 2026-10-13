<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['buyer_id', 'seller_id', 'product_id'], 'conversations_buyer_seller_product_index');
            $table->dropUnique('conversations_buyer_seller_unique');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_buyer_seller_product_index');
            $table->unique(['buyer_id', 'seller_id'], 'conversations_buyer_seller_unique');
        });
    }
};
