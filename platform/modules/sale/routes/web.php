<?php

use Illuminate\Support\Facades\Route;

Route::prefix(admin_prefix())
    ->middleware(['web', 'auth'])
    ->namespace('Polirium\Modules\Sale\Http\Controllers')
    ->group(function () {

    });
