<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RiderApiController;
use App\Http\Controllers\Api\ScannerApiController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Middleware\AuthenticateIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'service' => 'lumora-logistics',
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Dedicated Warehouse Barcode Scanner App API
    Route::prefix('scanner')->group(function () {
        Route::get('/status', [ScannerApiController::class, 'status']);
        Route::post('/login', [ScannerApiController::class, 'login']);
        Route::post('/scan', [ScannerApiController::class, 'scan']);
        Route::post('/batch-sync', [ScannerApiController::class, 'batchSync']);
        Route::get('/recent', [ScannerApiController::class, 'recentScans']);
    });

    // Mobile Rider API (Public)
    Route::post('/rider/login', [RiderApiController::class, 'login']);
    Route::post('/rider/send-otp', [RiderApiController::class, 'sendOtp']);
    Route::post('/rider/verify-otp', [RiderApiController::class, 'verifyOtp']);
    Route::post('/rider/register', [RiderApiController::class, 'register']);
    Route::get('/hubs', [RiderApiController::class, 'hubs']);

    // Mobile Rider API (Protected via Sanctum)
    Route::middleware('auth:sanctum')->prefix('rider')->group(function () {
        Route::get('/me', [RiderApiController::class, 'me']);
        Route::post('/logout', [RiderApiController::class, 'logout']);
        Route::post('/availability', [RiderApiController::class, 'updateAvailability']);
        Route::get('/deliveries', [RiderApiController::class, 'deliveries']);
        Route::get('/deliveries/available', [RiderApiController::class, 'availableDeliveries']);
        Route::post('/deliveries/{id}/accept', [RiderApiController::class, 'acceptDelivery']);
        Route::match(['post', 'patch'], '/deliveries/{id}/status', [RiderApiController::class, 'updateDeliveryStatus']);
        Route::get('/earnings', [RiderApiController::class, 'earnings']);

        // Mobile Scanner & Parcel Workflow Contract
        Route::get('/parcels', [RiderApiController::class, 'parcels']);
        Route::get('/parcels/{code}', [RiderApiController::class, 'showParcel']);
        Route::post('/parcels/{code}/scan', [RiderApiController::class, 'scanParcel']);
        Route::match(['post', 'patch'], '/parcels/{code}/status', [RiderApiController::class, 'updateParcelStatus']);

        // Operations Communication & Messages
        Route::get('/messages', [RiderApiController::class, 'messages']);
        Route::post('/messages', [RiderApiController::class, 'sendMessage']);
        Route::post('/chat/messages', [RiderApiController::class, 'sendMessage']);
        Route::get('/conversations', [RiderApiController::class, 'conversations']);
    });

    Route::post('/webhooks/order-events', [WebhookController::class, 'store']);

    Route::middleware(AuthenticateIntegration::class)->group(function () {
        Route::get('/me', fn (Request $request) => response()->json(['authenticated' => true]));
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'upsert']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::get('/shipments', [ShipmentController::class, 'index']);
        Route::post('/shipments', [ShipmentController::class, 'store']);
        Route::get('/shipments/{shipment}', [ShipmentController::class, 'show']);
        Route::post('/shipments/{shipment}/status', [ShipmentController::class, 'updateStatus']);
    });
});

