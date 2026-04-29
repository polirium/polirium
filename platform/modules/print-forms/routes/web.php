<?php

use Illuminate\Support\Facades\Route;

Route::prefix(admin_prefix())
    ->middleware(['web', 'auth'])
    ->namespace('Polirium\Modules\PrintForms\Http\Controllers')
    ->group(function () {
        Route::prefix('print-forms')->name('print-forms.')->group(function () {
            Route::prefix('editor')->name('editor.')->group(function () {
                Route::get('', 'SettingsController@editor')->name('index')->middleware('can:print-forms.forms.index');
                Route::get('print', 'SettingsController@print')->name('print')->middleware('can:print-forms.forms.index');
            });
        });
    });
