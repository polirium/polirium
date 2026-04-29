<?php

use Illuminate\Support\Facades\Route;

Route::prefix(admin_prefix())
    ->middleware(['web', 'auth'])
    ->namespace('Polirium\Modules\Vendor\Http\Controllers')
    ->group(function () {
        Route::prefix('vendors')->name('vendors.')->group(function () {
            Route::get('', 'VendorController@index')->name('index')->middleware('can:vendors.index');
            Route::get('group', 'VendorController@group')->name('group')->middleware('can:vendors.groups');

            Route::prefix('purchases')->name('purchases.')->group(function () {
                Route::get('', 'PurchaseController@index')->name('index')->middleware('can:vendors.purchases.index');
                Route::get('order/{id?}', 'PurchaseController@order')->name('order')->middleware('can:vendors.purchases.create');
                Route::get('show/{id}', 'PurchaseController@show')->name('show')->middleware('can:vendors.purchases.index');
                Route::get('export/{id}', 'PurchaseController@export')->name('export')->middleware('can:vendors.purchases.view');

                Route::get('list-refunds', 'PurchaseController@listRefund')->name('list-refunds')->middleware('can:vendors.refunds.index');
                Route::get('refund/{id?}', 'PurchaseController@refund')->name('refund')->middleware('can:vendors.refunds.index');
            });

            Route::prefix('transfers')->name('transfers.')->group(function () {
                Route::get('', 'TransferController@index')->name('index')->middleware('can:vendors.transfers.index');
                Route::get('transfer/{id?}', 'TransferController@transfer')->name('transfer')->middleware('can:vendors.transfers.edit');
            });
        });

    });
