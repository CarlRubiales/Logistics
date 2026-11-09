<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('commission_rules')) {
            Schema::create('commission_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('base_rate_centavos')->default(1800); // ₱18.00 per delivered parcel
                $table->unsignedTinyInteger('bonus_tier1_rate_pct')->default(90); // >= 90% on-time
                $table->unsignedInteger('bonus_tier1_centavos')->default(15000); // +₱150.00
                $table->unsignedTinyInteger('bonus_tier2_rate_pct')->default(95); // >= 95% on-time
                $table->unsignedInteger('bonus_tier2_centavos')->default(30000); // +₱300.00
                $table->unsignedInteger('deduction_per_return_centavos')->default(1800); // -₱18.00 per rider-fault return
                $table->string('period_type', 32)->default('semi_monthly'); // semi_monthly | weekly | monthly
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rider_commissions')) {
            Schema::create('rider_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rider_profile_id')->constrained('rider_profiles')->cascadeOnDelete();
                $table->string('hub_branch', 80)->index();
                $table->string('period_key', 32)->index();
                $table->string('period_label', 80);
                $table->date('period_start');
                $table->date('period_end');
                $table->unsignedInteger('delivered_count')->default(0);
                $table->unsignedInteger('on_time_count')->default(0);
                $table->unsignedInteger('on_time_rate_bp')->default(0); // 9500 = 95.00%
                $table->unsignedInteger('return_fault_count')->default(0);
                $table->json('return_parcel_ids')->nullable();
                $table->bigInteger('base_centavos')->default(0);
                $table->bigInteger('bonus_centavos')->default(0);
                $table->bigInteger('deductions_centavos')->default(0);
                $table->bigInteger('net_centavos')->default(0);
                $table->json('rules_snapshot')->nullable();
                $table->string('status', 24)->default('pending')->index(); // pending | approved | paid
                $table->boolean('is_flagged')->default(false)->index();
                $table->text('flagged_note')->nullable();
                $table->timestamp('flagged_at')->nullable();
                $table->foreignId('flagged_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('paid_at')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('payment_reference', 160)->nullable();
                $table->timestamps();

                $table->unique(['rider_profile_id', 'period_key'], 'rider_commissions_rider_period_unique');
            });
        } else {
            Schema::table('rider_commissions', function (Blueprint $table) {
                if (! Schema::hasColumn('rider_commissions', 'is_flagged')) {
                    $table->boolean('is_flagged')->default(false)->index();
                    $table->text('flagged_note')->nullable();
                    $table->timestamp('flagged_at')->nullable();
                    $table->foreignId('flagged_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_commissions');
        Schema::dropIfExists('commission_rules');
    }
};
