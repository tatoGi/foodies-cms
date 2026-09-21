<?php

use App\Http\Controllers\AiChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AI Chat routes
|--------------------------------------------------------------------------
| რეგისტრირდება bootstrap/app.php-ის then: callback-ში.
|
| stateless 'api' ჯგუფშია (CSRF-ის გარეშე), რომ Next.js ფრონტმაც
| (frontend/newhome) cross-origin-ით გამოიძახოს და Blade/Inertia საიტმაც.
|
| throttle აუცილებელია — საჯარო endpoint-ია და უფასო tier-ების
| ლიმიტებსაც იცავს და შენი ფასიანი API-ის ხარჯსაც.
*/

Route::middleware(['api', 'throttle:20,1'])->prefix('api/ai-chat')->group(function () {
    Route::post('/', [AiChatController::class, 'chat'])->name('aichat.chat');
    Route::post('/transcribe', [AiChatController::class, 'transcribe'])
        ->middleware('throttle:10,1')
        ->name('aichat.transcribe');
});
