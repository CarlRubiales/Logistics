<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ShipmentsController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ExceptionsController;
use App\Http\Controllers\LocationsController;
use App\Http\Controllers\IntegrationsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SortingCenterController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\AreasController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/track', [TrackingController::class, 'index'])->name('tracking.index');
Route::get('/track/{trackingNumber}', [TrackingController::class, 'index'])->name('tracking.show');
Route::get('/rider/register', [RiderController::class, 'registerForm'])->name('riders.register');
Route::post('/rider/register', [RiderController::class, 'register'])->name('riders.register.store');
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
Route::middleware(['auth', 'rider'])->group(function () {
    Route::get('/rider/dashboard', [RiderController::class, 'dashboard'])->name('riders.dashboard');
    Route::post('/parcels/{parcel}/accept', [SortingCenterController::class, 'riderAccept'])->name('parcels.accept');
    Route::post('/rider/scan', [RiderController::class, 'scanBarcode'])->name('riders.scan');
    Route::post('/rider/parcels/{parcel}/status', [RiderController::class, 'updateStatus'])->name('riders.status');
    Route::post('/rider/availability', [RiderController::class, 'toggleAvailability'])->name('riders.availability');
    Route::get('/rider/messages', [MessagesController::class, 'riderIndex'])->name('riders.messages');
    Route::post('/rider/messages', [MessagesController::class, 'send'])->name('riders.messages.send');
});
Route::middleware(['auth', 'logistics'])->group(function () {
    Route::get('/', [SortingCenterController::class, 'index'])->name('dashboard');
    Route::get('/shipments', [ShipmentsController::class, 'index'])->name('shipments.index');
    Route::post('/shipments/{shipment}/status', [ShipmentsController::class, 'updateStatus'])->name('shipments.status');
    Route::get('/shipments/{shipment}', [ShipmentsController::class, 'show'])->name('shipments.show');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::patch('/inventory/{inventoryItem}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::get('/exceptions', [ExceptionsController::class, 'index'])->name('exceptions.index');
    Route::post('/exceptions/{shipment}/resolve', [ExceptionsController::class, 'resolve'])->name('exceptions.resolve');
    Route::get('/locations', [LocationsController::class, 'index'])->name('locations.index');
    Route::post('/locations', [LocationsController::class, 'store'])->name('locations.store');
    Route::patch('/locations/{location}/toggle', [LocationsController::class, 'toggle'])->name('locations.toggle');
    Route::get('/integrations', [IntegrationsController::class, 'index'])->name('integrations.index');
    Route::patch('/integrations/{store}/toggle', [IntegrationsController::class, 'toggle'])->name('integrations.toggle');
    Route::post('/integrations/webhooks/{delivery}/retry', [IntegrationsController::class, 'retry'])->name('integrations.retry');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/messages', [MessagesController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessagesController::class, 'send'])->name('messages.send');
    Route::get('/areas', [AreasController::class, 'index'])->name('areas.index');
    Route::post('/areas', [AreasController::class, 'store'])->name('areas.store');
    Route::patch('/areas/{area}/toggle', [AreasController::class, 'toggle'])->name('areas.toggle');
    Route::get('/riders', [RiderController::class, 'index'])->name('riders.index');
    Route::post('/riders/{rider}/approval', [RiderController::class, 'approve'])->name('riders.approval');
    Route::post('/riders/{rider}/areas', [RiderController::class, 'assignArea'])->name('riders.areas');
    Route::delete('/riders/{rider}/areas/{area}', [RiderController::class, 'removeArea'])->name('riders.areas.destroy');
    Route::get('/sorting-center', [SortingCenterController::class, 'index'])->name('sorting-center.index');
    Route::post('/sorting-center/receive', [SortingCenterController::class, 'receive'])->name('sorting-center.receive');
    Route::post('/sorting-center/{parcel}/scan', [SortingCenterController::class, 'scan'])->name('sorting-center.scan');
    Route::post('/sorting-center/{parcel}/sort', [SortingCenterController::class, 'sort'])->name('sorting-center.sort');
    Route::post('/sorting-center/{parcel}/assign', [SortingCenterController::class, 'assign'])->name('sorting-center.assign');
    Route::get('/orders', [OrdersController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrdersController::class, 'show'])->name('orders.show');
});