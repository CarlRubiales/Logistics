<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('platform')->default('custom');
            $table->string('external_store_id')->nullable();
            $table->string('api_base_url')->nullable();
            $table->string('integration_key_hash')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['platform', 'is_active']);
        });

        Schema::create('fulfillment_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->index();
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country', 2)->default('US');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('order_number')->nullable();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->json('shipping_address')->nullable();
            $table->string('status')->default('paid')->index();
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamp('ordered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'external_id']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('quantity_fulfilled')->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fulfillment_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id')->nullable();
            $table->string('tracking_number')->nullable()->index();
            $table->string('carrier')->nullable();
            $table->string('service_level')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('destination_city')->nullable();
            $table->string('destination_state')->nullable();
            $table->string('destination_country', 2)->default('US');
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('label_url')->nullable();
            $table->string('exception_type')->nullable();
            $table->text('exception_note')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['order_id', 'external_id']);
        });

        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('occurred_at');
            $table->string('source')->default('system');
            $table->timestamps();
            $table->index(['shipment_id', 'occurred_at']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->string('signature')->nullable();
            $table->json('payload');
            $table->string('status')->default('received')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('tracking_events');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('fulfillment_locations');
        Schema::dropIfExists('stores');
    }
};
