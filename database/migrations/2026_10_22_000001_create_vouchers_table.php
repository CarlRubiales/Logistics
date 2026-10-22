<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('type', 20)->default('percent'); // percent, fixed, shipping
                $table->decimal('value', 10, 2)->default(0);
                $table->decimal('min_spend', 10, 2)->default(0);
                $table->integer('usage_limit')->nullable();
                $table->integer('used_count')->default(0);
                $table->date('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
