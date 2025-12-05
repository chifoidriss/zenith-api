<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('paying')->namespace('Paying')->group(function() {
    Route::prefix('contracts')->controller('ContractController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
        Route::get('filter' , 'filter');
    });

    Route::prefix('departments')->controller('DepartmentController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('absences')->controller('AbsenceController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('leaves')->controller('LeaveController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('reason-leaving')->controller('ReasonLeavingController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('posts')->controller('PostController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('contract-types')->controller('ContractTypeController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('leaving-types')->controller('LeavingTypeController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('bonuses')->controller('BonusController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('indemnities')->controller('IndemnityController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('salaries')->controller('SalaryController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('loans')->controller('LoanController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });

    Route::prefix('advances')->controller('AdvanceController')->group(function() {
        Route::get('' , 'index');
        Route::post('' , 'store');
        Route::get('{id}' , 'show');
        Route::delete('{id}' , 'destroy');
    });
});

