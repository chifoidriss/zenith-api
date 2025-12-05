<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('articles')->namespace('Article')->group(function() {
    Route::controller('CategoryController')->prefix('categories')->group(function() {
        Route::get('', 'index');
        Route::post('', 'store');
        Route::get('{id}', 'show');
        Route::delete('{id}', 'destroy');
    });

    Route::controller('UnitController')->prefix('units')->group(function() {
        Route::get('', 'index');
        Route::post('', 'store');
        Route::get('{id}', 'show');
        Route::delete('{id}', 'destroy');
    });

    Route::controller('ArticleController')->group(function() {
        Route::get('', 'index');
        Route::post('', 'store');
        Route::get('statistics', 'followup');
        Route::get('day-book', 'dayBook');
        Route::get('filter' , 'filter');
        Route::get('{id}', 'show');
        Route::delete('{id}', 'destroy');
        Route::delete('remove-price/{id}', 'removePrice');
        Route::delete('remove-menu-item/{id}', 'removeMenuItem');
        Route::get('free-up/{id}', 'freeUp');
        Route::post('check-validity', 'checkValidity');
    });
});

