<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RiderApiController;
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

    // Mobile Rider API (Public)
    Route::post('/rider/login', [RiderApiController::class, 'login']);
    Route::post('/rider/register', [RiderApiController::class, 'register']);

    // Mobile Rider API (Protected via Sanctum)
    Route::middleware('auth:sanctum')->prefix('rider')->group(function () {
        Route::get('/me', [RiderApiController::class, 'me']);
        Route::post('/logout', [RiderApiController::class, 'logout']);
        Route::post('/availability', [RiderApiController::class, 'updateAvailability']);
        Route::get('/deliveries', [RiderApiController::class, 'deliveries']);
        Route::get('/deliveries/available', [RiderApiController::class, 'availableDeliveries']);
        Route::post('/deliveries/{id}/accept', [RiderApiController::class, 'acceptDelivery']);
        Route::patch('/deliveries/{id}/status', [RiderApiController::class, 'updateDeliveryStatus']);
        Route::get('/earnings', [RiderApiController::class, 'earnings']);
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

