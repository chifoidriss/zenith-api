<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('stocking')->namespace('Stocking')->group(function() {
    Route::prefix('stocks')->controller('StockController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('warehouses')->controller('WareHouseController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('operations')->controller('OperationController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('transfers')->controller('TransferController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
        Route::post('confirm/{id}' , 'confirm');
        Route::post('duplicate/{id}' , 'duplicate');
        Route::delete('remove-item/{id}' , 'removeItem');
    });
});

