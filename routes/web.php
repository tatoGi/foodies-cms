<?php

use App\Http\Controllers\Checkout\CheckoutController;
use App\Http\Controllers\Mock\BogMockApiController;
use App\Http\Controllers\Mock\BogMockGatewayController;
use App\Models\Language;
use App\Support\BogMode;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes handle the main entry points for the web application.
| In this setup, the root URL is dedicated to the Admin Panel.
|
*/

Route::get('lang/{locale}', function (string $locale) {
    $allowedLocales = [];
    $fallbackLocale = config('app.fallback_locale', 'en');

    if (Schema::hasTable('languages')) {
        $allowedLocales = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('code')
            ->values()
            ->all();

        $fallbackLocale = Language::query()
            ->where('is_default', true)
            ->value('code')
            ?? ($allowedLocales[0] ?? $fallbackLocale);
    } else {
        $allowedLocales = collect(config('cms.locales', []))
            ->pluck('code')
            ->filter()
            ->values()
            ->all();
    }

    if (! in_array($locale, $allowedLocales, true)) {
        $locale = $fallbackLocale;
    }

    session(['locale' => $locale]);

    return redirect()->back();
})->name('lang.switch');

Route::prefix('checkout')->name('checkout.')->group(function (): void {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');

    Route::prefix('bog')->name('bog.')->group(function (): void {
        Route::post('/start', [CheckoutController::class, 'start'])->name('start');
        Route::get('/success', [CheckoutController::class, 'success'])->name('success');
        Route::get('/fail', [CheckoutController::class, 'fail'])->name('fail');
    });
});

if (BogMode::isMockMode()) {
    Route::prefix('mock/bog')->name('mock.bog.')->group(function (): void {
        Route::post('/token', [BogMockApiController::class, 'token'])->name('token');
        Route::post('/checkout/orders', [BogMockApiController::class, 'createOrder'])->name('orders');
        Route::get('/checkout/payment/{orderId}', [BogMockApiController::class, 'paymentDetails'])->name('payment-details');
        Route::get('/gateway/{orderId}', [BogMockGatewayController::class, 'show'])->name('gateway');
        Route::post('/gateway/{orderId}/complete', [BogMockGatewayController::class, 'complete'])->name('gateway.complete');
    });
}
