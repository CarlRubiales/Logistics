<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destination_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('coverage_rules')->nullable();
            $table->json('cities')->nullable();
            $table->json('postal_codes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['store_id', 'code']);
        });

        Schema::create('rider_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('license_number')->nullable();
            $table->string('registration_status')->default('pending')->index();
            $table->string('availability')->default('offline')->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('registration_note')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_area_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_area_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['rider_profile_id', 'destination_area_id'], 'rider_area_assignment_unique');
        });

        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('destination_area_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_rider_id')->nullable()->constrained('rider_profiles')->nullOnDelete();
            $table->string('parcel_number')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('recipient_name');
            $table->string('recipient_phone')->nullable();
            $table->text('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('status')->default('received')->index();
            $table->string('sort_bin')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamp('sorted_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('rider_received_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'status']);
            $table->index(['destination_area_id', 'status']);
        });

        Schema::create('parcel_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parcel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scan_type');
            $table->string('barcode')->nullable();
            $table->string('result')->default('success');
            $table->text('note')->nullable();
            $table->timestamp('scanned_at');
            $table->timestamps();
        });

        Schema::create('erp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recipient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parcel_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['recipient_id', 'read_at']);
        });

        Schema::create('commission_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parcel_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('pending')->index();
            $table->string('description')->nullable();
            $table->timestamp('earned_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_transactions');
        Schema::dropIfExists('erp_messages');
        Schema::dropIfExists('parcel_scans');
        Schema::dropIfExists('parcels');
        Schema::dropIfExists('rider_area_assignments');
        Schema::dropIfExists('rider_profiles');
        Schema::dropIfExists('destination_areas');
    }
};
