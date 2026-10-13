<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fulfillment_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku')->index();
            $table->string('name');
            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('quantity_reserved')->default(0);
            $table->unsignedInteger('reorder_point')->default(5);
            $table->string('status')->default('in_stock')->index();
            $table->timestamps();
            $table->unique(['store_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
