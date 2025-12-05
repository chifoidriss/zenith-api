
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('accounting')->namespace('Accounting')->group(function() {
    Route::prefix('chart-account')->controller('ChartAccountController')->group(function() {
        Route::get('filter/{code}', 'filterAccount');
        Route::get('type-account', 'indexTypeAccount');
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
        Route::post('depreciate/{id}', 'depreciate');
    });

    Route::prefix('entries')->controller('EntryController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('journals')->controller('JournalController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });
});
