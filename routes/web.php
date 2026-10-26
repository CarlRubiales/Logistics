<?php

use App\Http\Controllers\AccountModeController;
use App\Http\Controllers\Buyer\CartController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SellerApplicationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Seller\SellerDashboardController;
use App\Http\Controllers\Seller\ProductController;
use App\Http\Controllers\Seller\PayoutController;
use App\Http\Controllers\Seller\OrderController;
use App\Http\Controllers\Seller\ChatController as SellerChatController;
use App\Http\Controllers\Buyer\OrderController as BuyerOrderController;
use App\Http\Controllers\Buyer\ChatController as BuyerChatController;
use App\Http\Controllers\Buyer\PaymentMethodController;
use App\Http\Controllers\Buyer\ProductReviewController;
use App\Http\Controllers\Seller\ProfileController as SellerProfileController;
use App\Http\Controllers\Buyer\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'index'])->name('home');

// CSRF token refresh endpoint — returns a fresh token for AJAX form submissions
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->middleware('web')->name('csrf.token');

// Debug endpoint — helps diagnose 419 issues on production (remove after fix confirmed)
Route::post('/csrf-debug', function (\Illuminate\Http\Request $request) {
    return response()->json([
        'has_token_in_body'   => $request->has('_token'),
        'has_token_in_header' => $request->hasHeader('X-CSRF-TOKEN'),
        'token_match'         => $request->has('_token')
            ? hash_equals(csrf_token(), $request->input('_token'))
            : false,
        'session_id'          => session()->getId(),
        'csrf_token'          => csrf_token(),
        'post_keys'           => array_keys($request->post()),
        'content_length'      => $request->header('Content-Length'),
    ]);
})->middleware('web')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
Route::get('/about', function () {
    return view('about');
})->name('about');

Route::get('/dashboard', function () {
    return match (request()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'seller' => redirect()->route('seller.dashboard'),
        default => redirect()->route('shop.index'),
    };
})->middleware('auth')->name('dashboard');

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/application/{id}/{action}', [DashboardController::class, 'handleApplication'])->name('application');
    Route::get('/users', [DashboardController::class, 'users'])->name('users');
    Route::get('/applications', [DashboardController::class, 'applications'])->name('applications');
    Route::get('/users/{id}/{action}', [DashboardController::class, 'handleUserStatus'])->name('users.action');

    Route::get('/compliance', [DashboardController::class, 'compliance'])->name('compliance');
    Route::get('/compliance/product/{id}/flag', [DashboardController::class, 'flagProduct'])->name('compliance.flag');
    Route::get('/compliance/product/{id}/clear', [DashboardController::class, 'clearProduct'])->name('compliance.clear');
    Route::post('/compliance/seller/{id}/warn', [DashboardController::class, 'warnSeller'])->name('compliance.warn');

    Route::get('/complaints', [DashboardController::class, 'complaints'])->name('complaints');
    Route::post('/complaints/{id}/resolve', [DashboardController::class, 'resolveComplaint'])->name('complaints.resolve');
    Route::post('/complaints/{id}/dismiss', [DashboardController::class, 'dismissComplaint'])->name('complaints.dismiss');

    Route::get('/commission', [DashboardController::class, 'commission'])->name('commission');
    Route::get('/reports/sales-summary', [DashboardController::class, 'salesSummaryPdf'])->name('reports.salesSummary');
    Route::get('/reports/commission', [DashboardController::class, 'commissionReportPdf'])->name('reports.commission');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings.index');
    Route::post('/settings/announcements', [DashboardController::class, 'storeAnnouncement'])->name('settings.announcements.store');
    Route::patch('/settings/announcements/{id}/toggle', [DashboardController::class, 'toggleAnnouncement'])->name('settings.announcements.toggle');
    Route::patch('/settings/policies/{id}', [DashboardController::class, 'updatePolicy'])->name('settings.policies.update');

    Route::get('/vouchers', [DashboardController::class, 'vouchers'])->name('vouchers.index');
    Route::post('/vouchers', [DashboardController::class, 'storeVoucher'])->name('vouchers.store');
    Route::patch('/vouchers/{id}/toggle', [DashboardController::class, 'toggleVoucher'])->name('vouchers.toggle');
    Route::delete('/vouchers/{id}', [DashboardController::class, 'destroyVoucher'])->name('vouchers.destroy');

    Route::get('/chat', [ChatController::class, 'adminInbox'])->name('chat.index');
    Route::get('/chat/{id}', [ChatController::class, 'adminShow'])->name('chat.show');
});

// Seller routes
Route::middleware(['auth', 'role:seller'])->prefix('seller')->name('seller.')->group(function () {
    Route::get('/dashboard', [SellerDashboardController::class, 'index'])->name('dashboard');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::patch('/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::patch('/products/{id}/stock', [ProductController::class, 'updateStock'])->name('products.updateStock');
    Route::patch('/products/{id}/archive', [ProductController::class, 'archive'])->name('products.archive');
    Route::patch('/products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/method', [PayoutController::class, 'saveMethod'])->name('payouts.saveMethod');
    Route::post('/payouts/request', [PayoutController::class, 'requestPayout'])->name('payouts.request');
    Route::get('/payouts/reports/financial', [PayoutController::class, 'financialReportPdf'])->name('payouts.reports.financial');
    Route::get('/payouts/reports/performance', [PayoutController::class, 'performanceReportPdf'])->name('payouts.reports.performance');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/notifications', [OrderController::class, 'notifications'])->name('orders.notifications');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/pack', [OrderController::class, 'markPacked'])->name('orders.pack');
    Route::get('/orders/{id}/waybill', [OrderController::class, 'waybill'])->name('orders.waybill');

    Route::get('/chats', [SellerChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/{conversation}', [SellerChatController::class, 'show'])->name('chats.show');
    Route::post('/chats/{conversation}/messages', [SellerChatController::class, 'store'])->name('chats.messages.store');
    Route::post('/chats/{conversation}/read', [SellerChatController::class, 'read'])->name('chats.read');

    Route::get('/profile', [SellerProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [SellerProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [SellerProfileController::class, 'updatePassword'])->name('profile.updatePassword');

    // The route name becomes seller.switchToBuyer because it is inside the seller. group.
    Route::post('/switch-to-buyer', [AccountModeController::class, 'switchToBuyer'])
        ->name('switchToBuyer');
});

// Buyer/Guest routes
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/store/{seller}', [ShopController::class, 'sellerStore'])
    ->whereNumber('seller')
    ->name('shop.seller');
Route::get('/cart', [CartController::class, 'index'])->name('buyer.cart');
Route::post('/cart/select', [CartController::class, 'select'])->name('buyer.cart.select');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('buyer.cart.update');
Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('buyer.cart.remove');

Route::match(['GET', 'POST'], '/cart/checkout', [BuyerOrderController::class, 'create'])
    ->middleware('auth')
    ->name('buyer.cart.checkout');

Route::post('/cart/add/{product}', [CartController::class, 'add'])
    ->name('buyer.cart.add');



Route::middleware('auth')->group(function () {
    // This route intentionally stays outside the seller. group so its name is switchToSeller.
    // AccountModeController performs the seller-role check.
    Route::post('/switch-to-seller', [AccountModeController::class, 'switchToSeller'])
        ->name('switchToSeller');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/address', [ProfileController::class, 'updateAddress'])->name('profile.address.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/chat/my-conversation', [ChatController::class, 'myConversation'])->name('chat.mine');
    Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');

    Route::post('/shop/store/{seller}/chat', [BuyerChatController::class, 'startSeller'])
        ->whereNumber('seller')
        ->name('buyer.chats.start-seller');
    Route::post('/shop/store/{seller}/follow', [\App\Http\Controllers\Buyer\ShopFollowController::class, 'toggle'])
        ->whereNumber('seller')
        ->name('buyer.shops.follow');
    Route::get('/account/following', [\App\Http\Controllers\Buyer\ShopFollowController::class, 'index'])
        ->name('buyer.following.index');
    Route::post('/shop/product/{product}/chat', [BuyerChatController::class, 'startProduct'])
        ->whereNumber('product')
        ->name('buyer.chats.start-product');
    Route::get('/chats/start', [BuyerChatController::class, 'start'])
        ->name('buyer.chats.start');
    Route::post('/chats/start', [BuyerChatController::class, 'begin'])
        ->name('buyer.chats.begin');
    Route::get('/account/chats', [BuyerChatController::class, 'index'])
        ->name('buyer.chats.index');
    Route::get('/account/chats/{conversation}', [BuyerChatController::class, 'show'])
        ->name('buyer.chats.show');
    Route::post('/account/chats/{conversation}/messages', [BuyerChatController::class, 'store'])
        ->name('buyer.chats.messages.store');

    Route::view('/seller/application-pending', 'seller.pending')->name('seller.pending');
    Route::get('/seller/apply', [SellerApplicationController::class, 'create'])->name('seller.apply');
    Route::post('/seller/apply', [SellerApplicationController::class, 'store'])->name('seller.apply.store');

    Route::get('/shop/product/{id}', [ShopController::class, 'show'])
    ->whereNumber('id')
    ->name('shop.product');

    Route::post('/buy-now/{product}', [BuyerOrderController::class, 'buyNow'])
    ->name('buyer.buy-now');

    Route::get('/checkout', [BuyerOrderController::class, 'create'])
    ->name('buyer.buy-now.checkout');

    Route::post('/checkout/place-order', [BuyerOrderController::class, 'store'])
    ->name('buyer.checkout.store');

    Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store'])
    ->name('buyer.products.reviews.store');

    Route::patch('/products/{product}/reviews/{review}', [ProductReviewController::class, 'update'])
    ->name('buyer.products.reviews.update');

    Route::delete('/products/{product}/reviews/{review}', [ProductReviewController::class, 'destroy'])
    ->name('buyer.products.reviews.destroy');

    Route::get('/account/orders', [BuyerOrderController::class, 'index'])
    ->name('buyer.orders.index');

    Route::get('/account/wallet', [PaymentMethodController::class, 'index'])
    ->name('buyer.wallet.index');

    Route::post('/account/wallet', [PaymentMethodController::class, 'store'])
    ->name('buyer.wallet.store');

    Route::patch('/account/wallet/{paymentMethod}/default', [PaymentMethodController::class, 'setDefault'])
    ->name('buyer.wallet.default');

    Route::delete('/account/wallet/{paymentMethod}', [PaymentMethodController::class, 'destroy'])
    ->name('buyer.wallet.destroy');

    Route::get('/account/payment-methods', [PaymentMethodController::class, 'index'])
    ->name('buyer.payment-methods.index');

    Route::post('/account/payment-methods', [PaymentMethodController::class, 'store'])
    ->name('buyer.payment-methods.store');

    Route::patch('/account/payment-methods/{paymentMethod}/default', [PaymentMethodController::class, 'setDefault'])
    ->name('buyer.payment-methods.default');

    Route::delete('/account/payment-methods/{paymentMethod}', [PaymentMethodController::class, 'destroy'])
    ->name('buyer.payment-methods.destroy');

    Route::get('/orders/{order}', [BuyerOrderController::class, 'show'])
    ->name('buyer.orders.show');


});

// Email verification
Route::post('/register/send-code', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'sendCode'])->name('register.sendCode');
Route::post('/register/verify-code', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'verifyCode'])->name('register.verifyCode');

// Address lookups
Route::get('/address/provinces', [\App\Http\Controllers\AddressController::class, 'provinces'])->name('address.provinces');
Route::get('/address/provinces/{code}/municipalities', [\App\Http\Controllers\AddressController::class, 'municipalities'])->name('address.municipalities');
Route::get('/address/municipalities/{code}/barangays', [\App\Http\Controllers\AddressController::class, 'barangays'])->name('address.barangays');

// Google OAuth routes
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])
    ->name('google.redirect');

Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
    ->name('google.callback');



require __DIR__.'/auth.php';
