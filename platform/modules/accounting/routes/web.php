<?php

use Illuminate\Support\Facades\Route;

Route::prefix(admin_prefix())
    ->middleware(['web', 'auth'])
    ->namespace('Polirium\Modules\Accounting\Http\Controllers')
    ->group(function () {
        Route::prefix('accountings')->name('accountings.')->group(function () {
            Route::get('', 'AccountingController@index')->name('index')->middleware('can:accountings.index');
            Route::get('invoices', 'AccountingController@invoice')->name('invoice')->middleware('can:accountings.invoices');

            Route::prefix('payment')->name('payment.')->group(function () {
                Route::get('', 'AccountingController@paymentIndex')->name('index')->middleware('can:accountings.payments');
                Route::get('refund', 'AccountingController@paymentRefund')->name('refund')->middleware('can:accountings.refunds');
                Route::get('refund/{id}', 'AccountingController@paymentRefundDetail')->name('refund.detail')->middleware('can:accountings.refunds');
                Route::get('export/{id}', 'AccountingController@exportInvoice')->name('export')->middleware('can:accountings.payments');
                Route::get('copy/{id}', 'AccountingController@copyInvoice')->name('copy')->middleware('can:accountings.payments');
                Route::get('{id}', 'AccountingController@show')->name('show')->where('id', '[0-9]+')->middleware('can:accountings.payments');
            });
            Route::prefix('report')->name('report.')->group(function () {
                Route::get('sales', 'AccountingController@salesReport')->name('sales')->middleware('can:accountings.payments');
            });
        });
    });
