<?php

use App\Http\Controllers\Installer\RunInstallationStageController;
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

Route::get('/', function () {
    return view('welcome');
});

// Keep the installer page in Laravel's web middleware group so its initial
// response and the staged installation requests share a session and CSRF token.
Route::get('/install', Installer::class)
    ->name('installer.index');

Route::post('/install/stage', RunInstallationStageController::class)
    ->name('installer.stage');
