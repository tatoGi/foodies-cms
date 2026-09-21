<?php

use App\Http\Controllers\Pos\PosHeartbeatController;
use App\Http\Controllers\Pos\PosMenuSnapshotController;
use App\Http\Controllers\Website\AuthController;
use App\Http\Controllers\Website\BootstrapController;
use App\Http\Controllers\Website\CartController;
use App\Http\Controllers\Website\CheckoutController;
use App\Http\Controllers\Website\ContactSubmissionController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\NavigationController;
use App\Http\Controllers\Website\PageController;
use App\Http\Controllers\Website\PostController;
use App\Http\Controllers\Website\ProductController;
use App\Http\Controllers\Website\SearchController;
use App\Http\Controllers\Website\WishlistController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Register stateless API endpoints here.
| This file is loaded with the "api" middleware group.
|
*/

// FoodEase POS -> CMS integration (internal; see docs/INTEGRATION_MASTER_PLAN.md §5)
Route::prefix('pos/v1')->middleware(\App\Http\Middleware\AuthenticatePosDevice::class)->group(function () {
    Route::post('/heartbeat', PosHeartbeatController::class)->name('api.pos.heartbeat');
    Route::post('/sync/menu/snapshot', PosMenuSnapshotController::class)->name('api.pos.menu.snapshot');
});

Route::prefix('web')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('api.website.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.website.auth.login');
        Route::middleware(\App\Http\Middleware\AuthenticateFrontendUser::class)->group(function () {
            Route::get('/me', [AuthController::class, 'me'])->name('api.website.auth.me');
            Route::put('/me', [AuthController::class, 'update'])->name('api.website.auth.update');
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.website.auth.logout');
        });
    });

    Route::middleware(\App\Http\Middleware\AuthenticateFrontendUser::class)->prefix('checkout')->group(function () {
        Route::get('/summary', [CheckoutController::class, 'summary'])->name('api.website.checkout.summary');
        Route::get('/result', [CheckoutController::class, 'result'])->name('api.website.checkout.result');
        Route::post('/start', [CheckoutController::class, 'start'])->name('api.website.checkout.start');
        Route::post('/cards/pay', [CheckoutController::class, 'payWithSavedCard'])->name('api.website.checkout.cards.pay');
        Route::post('/cards/{card}/default', [CheckoutController::class, 'setDefaultCard'])->name('api.website.checkout.cards.default');
        Route::delete('/cards/{card}', [CheckoutController::class, 'deleteCard'])->name('api.website.checkout.cards.delete');
    });

    Route::middleware(\App\Http\Middleware\AuthenticateFrontendUser::class)->prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('api.website.wishlist.index');
        Route::post('/', [WishlistController::class, 'store'])->name('api.website.wishlist.store');
        Route::delete('/{productId}', [WishlistController::class, 'destroy'])->name('api.website.wishlist.destroy');
    });

    Route::get('/search', SearchController::class)->name('api.website.search');
    Route::get('/bootstrap', BootstrapController::class)->name('api.website.bootstrap');
    Route::get('/navigation', NavigationController::class)->name('api.website.navigation');
    Route::get('/home', HomeController::class)->name('api.website.home');
    Route::post('/contact-submissions', [ContactSubmissionController::class, 'store'])->name('api.website.contact-submissions.store');
    Route::post('/call-requests', [ContactSubmissionController::class, 'storeCallRequest'])->name('api.website.call-requests.store');
    Route::get('/cart', [CartController::class, 'index'])->name('api.website.cart.index');
    Route::post('/cart/items', [CartController::class, 'store'])->name('api.website.cart.store');
    Route::patch('/cart/items/{productId}', [CartController::class, 'update'])->name('api.website.cart.update');
    Route::delete('/cart/items/{productId}', [CartController::class, 'destroy'])->name('api.website.cart.destroy');

    // Dynamically slug-based routes
    Route::get('/blog/{slug}', [PostController::class, 'show'])->name('api.website.blog.show');
    Route::get('/projects/{slug}', [PostController::class, 'show'])->name('api.website.project.show');
    Route::get('/project/{slug}', [PostController::class, 'show'])->name('api.website.project.singular.show');
    Route::get('/services/{slug}', [PostController::class, 'show'])->name('api.website.service.show');
    Route::get('/service/{slug}', [PostController::class, 'show'])->name('api.website.service.singular.show');
    Route::get('/products', [ProductController::class, 'index'])->name('api.website.products.index');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('api.website.product.show');

    // Legacy/Fallback
    Route::get('/news/{slug}', [PostController::class, 'show'])->name('api.website.post.show');
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('api.website.page.show');
});
