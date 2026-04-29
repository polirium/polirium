<?php

use Illuminate\Support\Facades\Route;
use Polirium\Modules\Product\Http\Controllers\PaymentMethodController;
use Polirium\Modules\Product\Http\Controllers\ProductController;
use Polirium\Modules\Product\Http\Controllers\StockController;

Route::prefix(admin_prefix())
->middleware(['web', 'auth'])
->group(function () {
    Route::prefix('products')
        ->name('products.')
        ->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index')->middleware('can:products.index');

            Route::get('payment', [ProductController::class, 'payment'])->name('payment')->middleware('can:sales.payment.index');
            Route::get('payment-v2', [ProductController::class, 'paymentV2'])->name('payment.v2')->middleware('can:sales.payment.index');
            Route::get('payment/refund/{id}', [ProductController::class, 'refund'])->name('payment.refund')->middleware('can:sales.payment.refund');

            Route::get('price-setting', [ProductController::class, 'priceSetting'])->name('price-setting')->middleware('can:products.price-setting');

            Route::prefix('stock')->name('stock.')->group(function () {
                Route::get('/', [StockController::class, 'index'])->name('index')->middleware('can:products.stock.index');
                Route::get('show/{id}', [StockController::class, 'show'])->name('show')->middleware('can:products.stock.view');
                Route::get('stock/{id?}', [StockController::class, 'stock'])->name('stock')->middleware('can:products.stock.manage');
            });

            Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
                Route::get('/', [PaymentMethodController::class, 'index'])->name('index')->middleware('can:sales.payment.index');
            });

            Route::prefix('print')->name('print.')->group(function () {
                Route::get('payment/{id}', [ProductController::class, 'printPayment'])->name('print-payment')->middleware('can:sales.print');
            });

            Route::prefix('sale-channel')->name('sale-channel.')->group(function () {
                Route::get('/', [\Polirium\Modules\Product\Http\Controllers\SaleChannelController::class, 'index'])->name('index')->middleware('can:products.sale-channel');
            });

            Route::prefix('delivery-partner')->name('delivery-partner.')->group(function () {
                Route::get('/', [\Polirium\Modules\Product\Http\Controllers\DeliveryPartnerController::class, 'index'])->name('index')->middleware('can:products.delivery-partner');
            });
        });
});
