<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('invoicing')->namespace('Invoicing')->group(function() {
    Route::controller('PaymentController')->prefix('payments/{type}')->group(function() {
        Route::get('', 'index');
        Route::post('', 'store');
        Route::get('{id}', 'show');
        Route::delete('{id}', 'destroy');
        Route::post('{id}/cancel', 'cancel');
    });

    Route::controller('PaymentMethodController')->prefix('payment-methods')->group(function() {
        Route::get('', 'index');
        Route::post('', 'store');
        Route::get('{id}', 'show');
        Route::delete('{id}', 'destroy');
    });

    Route::controller('DeviseController')->prefix('devises')->group(function() {
        Route::get('', 'index');
        Route::post('', 'store');
        Route::get('{id}', 'show');
        Route::delete('{id}', 'destroy');
    });

    Route::controller('TaxeController')->prefix('taxes')->group(function() {
        Route::get('', 'index');
        Route::post('', 'store');
        Route::get('{id}', 'show');
        Route::delete('{id}', 'destroy');
    });

    Route::controller('InvoiceController')->group(function() {
        Route::get('statistics', 'statistics');
        Route::get('followup/{type}', 'followup');

        Route::prefix('{invoice_type}/{type}')->group(function() {
            Route::get('', 'index');
            Route::post('', 'store');
            Route::get('{id}', 'show');
            Route::delete('{id}', 'destroy');
            Route::post('confirm/{id}', 'confirm');
            Route::post('duplicate/{id}', 'duplicate');
            Route::delete('remove-item/{id}', 'removeItem');
        });
    });

    Route::controller('SocietyController')->prefix('societies')->group(function() {
        // Route::get('', 'index');
        Route::post('', 'store');
        Route::get('', 'show');
        // Route::delete('{id}', 'destroy');
    });
});
