<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('partners/{type}')->namespace('Partner')->group(function() {
    Route::controller('PartnerController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('filter' , 'filter');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });
});

