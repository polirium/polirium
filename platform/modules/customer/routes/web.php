<?php

use Illuminate\Support\Facades\Route;

Route::prefix(admin_prefix())
    ->middleware(['web', 'auth'])
    ->namespace('Polirium\Modules\Customer\Http\Controllers')
    ->group(function () {
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('', 'CustomerController@index')->name('index')->middleware('can:customers.index');
            Route::get('group', 'CustomerController@group')->name('group')->middleware('can:customers.groups');
        });
    });
