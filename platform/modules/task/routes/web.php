<?php

use Illuminate\Support\Facades\Route;
use Polirium\Modules\Task\Http\Controllers\ProjectController;
use Polirium\Modules\Task\Http\Controllers\TaskController;

Route::prefix('admin')->name('admin.')->middleware(['web', 'auth'])->group(function () {
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index')->middleware('can:projects.index');
        Route::get('create', [ProjectController::class, 'create'])->name('create')->middleware('can:projects.create');
        Route::post('/', [ProjectController::class, 'store'])->name('store')->middleware('can:projects.create');
        Route::get('{id}', [ProjectController::class, 'show'])->name('show')->middleware('can:projects.show');
        Route::get('{id}/edit', [ProjectController::class, 'edit'])->name('edit')->middleware('can:projects.edit');
        Route::put('{id}', [ProjectController::class, 'update'])->name('update')->middleware('can:projects.edit');
        Route::delete('{id}', [ProjectController::class, 'destroy'])->name('destroy')->middleware('can:projects.destroy');
    });

    Route::prefix('tasks')->name('tasks.')->group(function () {
        // Gantt & Kanban PHẢI trước {id} để tránh bị match wildcard
        Route::get('gantt', [TaskController::class, 'gantt'])->name('gantt')->middleware('can:tasks.index');
        Route::get('kanban', [TaskController::class, 'kanban'])->name('kanban')->middleware('can:tasks.index');

        Route::get('/', [TaskController::class, 'index'])->name('index')->middleware('can:tasks.index');
        Route::get('create', [TaskController::class, 'create'])->name('create')->middleware('can:tasks.create');
        Route::post('/', [TaskController::class, 'store'])->name('store')->middleware('can:tasks.create');
        Route::get('{id}', [TaskController::class, 'show'])->name('show')->middleware('can:tasks.show');
        Route::get('{id}/edit', [TaskController::class, 'edit'])->name('edit')->middleware('can:tasks.edit');
        Route::put('{id}', [TaskController::class, 'update'])->name('update')->middleware('can:tasks.edit');
        Route::delete('{id}', [TaskController::class, 'destroy'])->name('destroy')->middleware('can:tasks.destroy');
    });
});
