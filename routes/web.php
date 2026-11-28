<?php

use App\Http\Controllers\CommerceAccountController;
use App\Http\Controllers\CommerceAdminController;
use App\Http\Controllers\CommerceController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', [CommerceController::class, 'home'])->name('home');
Route::get('/shop', [CommerceController::class, 'index'])->name('shop');
Route::get('/shop/{product}', [CommerceController::class, 'show'])->name('shop.product');
Route::get('/cart', [CommerceController::class, 'cart'])->name('cart');
Route::post('/cart/{product}', [CommerceController::class, 'addToCart'])
    ->middleware('throttle:commerce-cart')->block(5, 5)->name('cart.add');
Route::patch('/cart/{product}', [CommerceController::class, 'updateCart'])
    ->middleware('throttle:commerce-cart')->block(5, 5)->name('cart.update');
Route::delete('/cart/{product}', [CommerceController::class, 'removeFromCart'])
    ->middleware('throttle:commerce-cart')->block(5, 5)->name('cart.remove');
Route::get('/checkout', [CommerceController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [CommerceController::class, 'placeOrder'])
    ->middleware('throttle:commerce-checkout')->block(10, 10)->name('checkout.store');
Route::get('/orders/{order}', [CommerceController::class, 'confirmation'])
    ->middleware('signed')->name('order.confirmation');

Route::middleware(['auth', 'verified', 'role.commerce:buyer,seller'])->group(function () {
    Route::get('/account', [CommerceAccountController::class, 'index'])->name('account');
    Route::get('/account/orders/{order:id}', [CommerceAccountController::class, 'show'])->name('account.orders.show');
});

Route::middleware(['auth', 'verified', 'role.commerce:seller'])
    ->prefix('seller')->name('seller.')->group(function () {
        Route::get('/dashboard', [SellerController::class, 'index'])->name('dashboard');
        Route::post('/products', [SellerController::class, 'storeProduct'])->name('products.store');
        Route::patch('/products/{product}', [SellerController::class, 'updateProduct'])->name('products.update');
        Route::patch('/products/{product}/submit', [SellerController::class, 'submitProduct'])->name('products.submit');
    });

Route::middleware(['auth', 'verified', 'role.commerce'])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('/commerce', [CommerceAdminController::class, 'index'])->name('commerce.index');
        Route::post('/commerce/products', [CommerceAdminController::class, 'storeProduct'])->name('commerce.products.store');
        Route::patch('/commerce/products/{product}', [CommerceAdminController::class, 'updateProduct'])->name('commerce.products.update');
        Route::patch('/commerce/products/{product}/approve', [CommerceAdminController::class, 'approveProduct'])->name('commerce.products.approve');
        Route::patch('/commerce/products/{product}/reject', [CommerceAdminController::class, 'rejectProduct'])->name('commerce.products.reject');
        Route::patch('/commerce/products/{product}/archive', [CommerceAdminController::class, 'archiveProduct'])->name('commerce.products.archive');
        Route::patch('/commerce/orders/{order:id}/status', [CommerceAdminController::class, 'updateOrderStatus'])->name('commerce.orders.status');
        Route::patch('/commerce/orders/{order:id}/payment-status', [CommerceAdminController::class, 'updateOrderPaymentStatus'])->name('commerce.orders.payment-status');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/bulk', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::match(['get', 'post'], '/users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
    });

Route::get('/dashboard', function () {
    $user = Auth::user();
    if (! $user) {
        return redirect()->route('login');
    }

    return redirect(match (true) {
        $user->isSuperadmin() => route('admin.commerce.index'),
        $user->isSeller() => route('seller.dashboard'),
        default => route('account'),
    });
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/users', fn () => redirect()->route('dashboard'))->name('users');
    Route::match(['get', 'post'], '/leave-impersonation', [UserController::class, 'leaveImpersonation'])->name('users.leave-impersonation');
});

Route::get('/privacy-policy', fn () => Inertia::render('Legal/PrivacyPolicy'))->name('privacy');
Route::get('/terms-of-service', fn () => Inertia::render('Legal/TermsOfService'))->name('terms');
Route::get('/cookie-policy', fn () => Inertia::render('Legal/CookiePolicy'))->name('cookies');

if (! Features::enabled(Features::registration())) {
    Route::match(['get', 'post'], '/register', fn () => redirect()->route('login'))->name('register');
}

require __DIR__.'/settings.php';
