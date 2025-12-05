<?php

use App\Http\Controllers\Auth\CallbackController;
use App\Http\Controllers\Auth\CheckController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\RefreshController;
use App\Http\Controllers\Auth\WebhookController;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    // OAuth callback route
    Route::get('/callback', CallbackController::class)->name('auth.callback');

    // Session management routes
    Route::get('/check', CheckController::class)->name('auth.check');
    Route::post('/refresh', RefreshController::class)->name('auth.refresh');
    Route::get('/me', MeController::class)->name('auth.me');
    Route::post('/logout', LogoutController::class)->name('auth.logout');

    // Webhook route
    Route::post('/webhook', WebhookController::class)->name('auth.webhook');
});

Route::middleware(['auth:sanctum', 'log'])
    ->namespace('App\Http\Controllers')
    ->group(function () {
    $pages = scandir(base_path('routes/modules'));

    Route::get('countries', 'Account\AccountController@countries');
    Route::get('print/{type}/{id}', 'ExportController@printToPDF');
    
    foreach ($pages as $filename) {
        if (Str::endsWith($filename, '.php')) {
            require base_path("routes/modules/$filename");
        }
    }
});

Route::get('a', function () {
    return Carbon::parse('2024-02-01')->lastOfMonth();
});

Route::get('header', function (Request $request) {
    return response()->json(request()->header('enterprise'));
});
