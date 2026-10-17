<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Installer\RunInstallationStageController;
use App\Http\Controllers\SystemUpdateController;
use App\Http\Livewire\Installer;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Homepage: the public website, or straight to the admin (Settings → System).
Route::get('/', HomeController::class)->name('home');

// Keep the installer page in Laravel's web middleware group so its initial
// response and the staged installation requests share a session and CSRF token.
Route::get('/install', Installer::class)
    ->name('installer.index');

Route::post('/install/stage', RunInstallationStageController::class)
    ->name('installer.stage');

// System update from GitHub releases (super administrators only).
Route::middleware(['auth', 'can:system.update'])
    ->prefix(admin_prefix() . '/system/update')
    ->name('system.update.')
    ->group(function () {
        Route::get('/', [SystemUpdateController::class, 'index'])->name('index');
        Route::post('check', [SystemUpdateController::class, 'check'])->name('check');
        Route::post('stage', [SystemUpdateController::class, 'stage'])->name('stage');
        Route::post('rollback', [SystemUpdateController::class, 'rollback'])->name('rollback');
    });
