<?php

use Illuminate\Support\Facades\Route;

Route::prefix('account')->namespace('Account')->group(function() {
    Route::controller('AccountController')->group(function() {
        Route::post('update-image', 'AccountController@updateFile');
        Route::post('update-informations', 'AccountController@update');
        Route::post('update-password', 'AccountController@updatePassword');
    });

    Route::controller('UserController')->prefix('users')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::controller('RoleController')->prefix('roles')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('permissions' , 'permissions');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });
});
