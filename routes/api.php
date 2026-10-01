<?php

use App\Http\Controllers\Pos\PosHeartbeatController;
use App\Http\Controllers\Pos\PosMenuSnapshotController;
use App\Http\Controllers\Website\AuthController;
use App\Http\Controllers\Website\BootstrapController;
use App\Http\Controllers\Website\CartController;
use App\Http\Controllers\Website\CheckoutController;
use App\Http\Controllers\Website\ContactSubmissionController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\MenuController;
use App\Http\Controllers\Website\NavigationController;
use App\Http\Controllers\Website\PageController;
use App\Http\Controllers\Website\PostController;
use App\Http\Controllers\Website\ProductController;
use App\Http\Controllers\Website\SearchController;

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
    Route::post('/sync/sales', \App\Http\Controllers\Pos\PosSalesController::class)->name('api.pos.sales');
});

Route::prefix('web')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:web-register');
        Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->middleware('throttle:web-login');
        Route::post('/verify-email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:web-codes');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:web-login');
        Route::post('/google', [AuthController::class, 'google'])->middleware('throttle:web-login');
        Route::post('/password/forgot', [AuthController::class, 'forgotPassword'])->middleware('throttle:web-codes');
        Route::post('/password/reset', [AuthController::class, 'resetPassword'])->middleware('throttle:web-login');
        Route::middleware(\App\Http\Middleware\AuthenticateFrontendUser::class)
            ->post('/logout', [AuthController::class, 'logout']);
    });

    Route::middleware(\App\Http\Middleware\AuthenticateFrontendUser::class)->prefix('me')->group(function () {
        Route::get('/', [\App\Http\Controllers\Website\ProfileController::class, 'show'])->name('api.website.me');
        Route::put('/', [\App\Http\Controllers\Website\ProfileController::class, 'update']);
        Route::delete('/', [\App\Http\Controllers\Website\ProfileController::class, 'destroy']);
        Route::put('/password', [\App\Http\Controllers\Website\ProfileController::class, 'password']);
        Route::get('/addresses', [\App\Http\Controllers\Website\AddressController::class, 'index']);
        Route::post('/addresses', [\App\Http\Controllers\Website\AddressController::class, 'store']);
        Route::put('/addresses/{id}', [\App\Http\Controllers\Website\AddressController::class, 'update'])->whereNumber('id');
        Route::delete('/addresses/{id}', [\App\Http\Controllers\Website\AddressController::class, 'destroy'])->whereNumber('id');
        Route::get('/favorites', [\App\Http\Controllers\Website\FavoriteController::class, 'index']);
        Route::post('/favorites', [\App\Http\Controllers\Website\FavoriteController::class, 'store']);
        Route::delete('/favorites/{productId}', [\App\Http\Controllers\Website\FavoriteController::class, 'destroy'])->whereNumber('productId');
    });

    Route::middleware(\App\Http\Middleware\AuthenticateFrontendUser::class)->prefix('checkout')->group(function () {
        Route::get('/summary', [CheckoutController::class, 'summary'])->name('api.website.checkout.summary');
        Route::get('/result', [CheckoutController::class, 'result'])->name('api.website.checkout.result');
        Route::post('/start', [CheckoutController::class, 'start'])->name('api.website.checkout.start');
        Route::post('/cards/pay', [CheckoutController::class, 'payWithSavedCard'])->name('api.website.checkout.cards.pay');
        Route::post('/cards/{card}/default', [CheckoutController::class, 'setDefaultCard'])->name('api.website.checkout.cards.default');
        Route::delete('/cards/{card}', [CheckoutController::class, 'deleteCard'])->name('api.website.checkout.cards.delete');
    });

    Route::get('/search', SearchController::class)->name('api.website.search');
    Route::get('/bootstrap', BootstrapController::class)->name('api.website.bootstrap');
    Route::get('/navigation', NavigationController::class)->name('api.website.navigation');
    Route::get('/home', HomeController::class)->name('api.website.home');
    Route::get('/menu', [MenuController::class, 'index'])->name('api.website.menu');
    Route::get('/menu/categories/{slug}', [MenuController::class, 'category'])->name('api.website.menu.category');
    Route::get('/status', [MenuController::class, 'status'])->name('api.website.status');
    Route::post('/contact-submissions', [ContactSubmissionController::class, 'store'])->name('api.website.contact-submissions.store');
    Route::get('/cart', [CartController::class, 'index'])->name('api.website.cart.index');
    Route::post('/cart/items', [CartController::class, 'store'])->name('api.website.cart.store');
    Route::patch('/cart/items/{productId}', [CartController::class, 'update'])->name('api.website.cart.update');
    Route::delete('/cart/items/{productId}', [CartController::class, 'destroy'])->name('api.website.cart.destroy');

    // Dynamically slug-based routes
    Route::get('/posts', [PostController::class, 'index'])->name('api.website.posts.index');
    Route::get('/blog/{slug}', [PostController::class, 'show'])->name('api.website.blog.show');
    Route::get('/projects/{slug}', [PostController::class, 'show'])->name('api.website.project.show');
    Route::get('/project/{slug}', [PostController::class, 'show'])->name('api.website.project.singular.show');
    Route::get('/services/{slug}', [PostController::class, 'show'])->name('api.website.service.show');
    Route::get('/service/{slug}', [PostController::class, 'show'])->name('api.website.service.singular.show');
    Route::get('/products', [ProductController::class, 'index'])->name('api.website.products.index');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('api.website.product.show');

    // Legacy/Fallback
    Route::get('/news/{slug}', [PostController::class, 'show'])->name('api.website.post.show');
    Route::get('/pages', [PageController::class, 'index'])->name('api.website.pages.index');
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('api.website.page.show');
});
