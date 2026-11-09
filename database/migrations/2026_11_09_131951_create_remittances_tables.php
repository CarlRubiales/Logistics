<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_profile_id')->constrained('rider_profiles')->cascadeOnDelete();
            $table->foreignId('hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->string('hub_branch')->index();
            $table->date('remit_date')->index();
            $table->string('status')->default('awaiting_cashier')->index(); // not_remitted, awaiting_cashier, remitted, short, over, nothing_due
            $table->bigInteger('expected_amount_centavos')->default(0);
            $table->bigInteger('declared_amount_centavos')->default(0);
            $table->bigInteger('counted_amount_centavos')->nullable();
            $table->bigInteger('difference_centavos')->default(0); // counted - expected
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable()->index();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cashier_note')->nullable();
            $table->text('rider_note')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();

            $table->unique(['rider_profile_id', 'remit_date']);
        });

        Schema::create('remittance_parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('remittance_id')->constrained('remittances')->cascadeOnDelete();
            $table->foreignId('parcel_id')->constrained('parcels')->cascadeOnDelete();
            $table->string('parcel_number')->index();
            $table->bigInteger('cod_amount_centavos')->default(0);
            $table->string('status')->default('delivered');
            $table->timestamps();

            $table->unique(['remittance_id', 'parcel_id']);
        });

        if (Schema::hasTable('hubs') && ! Schema::hasColumn('hubs', 'remit_cutoff_time')) {
            Schema::table('hubs', function (Blueprint $table) {
                $table->time('remit_cutoff_time')->default('20:00:00')->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hubs') && Schema::hasColumn('hubs', 'remit_cutoff_time')) {
            Schema::table('hubs', function (Blueprint $table) {
                $table->dropColumn('remit_cutoff_time');
            });
        }

        Schema::dropIfExists('remittance_parcels');
        Schema::dropIfExists('remittances');
    }
};
