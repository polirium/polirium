<?php

use Illuminate\Support\Facades\Route;
use Polirium\Modules\Task\Http\Controllers\ProjectController;
use Polirium\Modules\Task\Http\Controllers\TaskController;
use Polirium\Modules\Task\Http\Controllers\TaskDetailController;

Route::prefix('admin')->name('admin.')->middleware(['web', 'auth'])->group(function () {
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index')->middleware('can:projects.index');
        Route::get('create', [ProjectController::class, 'create'])->name('create')->middleware('can:projects.create');
        Route::post('/', [ProjectController::class, 'store'])->name('store')->middleware('can:projects.create');
        Route::get('{id}', [ProjectController::class, 'show'])->name('show')->middleware('can:projects.show')->whereNumber('id');
        Route::get('{id}/edit', [ProjectController::class, 'edit'])->name('edit')->middleware('can:projects.edit')->whereNumber('id');
        Route::put('{id}', [ProjectController::class, 'update'])->name('update')->middleware('can:projects.edit')->whereNumber('id');
        Route::delete('{id}', [ProjectController::class, 'destroy'])->name('destroy')->middleware('can:projects.destroy')->whereNumber('id');
    });

    Route::prefix('tasks')->name('tasks.')->group(function () {
        // Gantt & Kanban PHẢI trước {id} để tránh bị match wildcard
        Route::get('gantt', [TaskController::class, 'gantt'])->name('gantt')->middleware('can:tasks.gantt');
        Route::get('kanban', [TaskController::class, 'kanban'])->name('kanban')->middleware('can:tasks.kanban');

        Route::get('/', [TaskController::class, 'index'])->name('index')->middleware('can:tasks.index');
        Route::get('create', [TaskController::class, 'create'])->name('create')->middleware('can:tasks.create');
        Route::post('/', [TaskController::class, 'store'])->name('store')->middleware('can:tasks.create');
        Route::get('{id}', [TaskController::class, 'show'])->name('show')->middleware('can:tasks.show')->whereNumber('id');
        Route::get('{id}/edit', [TaskController::class, 'edit'])->name('edit')->middleware('can:tasks.edit')->whereNumber('id');
        Route::put('{id}', [TaskController::class, 'update'])->name('update')->middleware('can:tasks.edit')->whereNumber('id');
        Route::patch('{id}/status', [TaskController::class, 'updateStatus'])->name('status')->middleware('can:tasks.edit')->whereNumber('id');
        Route::delete('{id}', [TaskController::class, 'destroy'])->name('destroy')->middleware('can:tasks.destroy')->whereNumber('id');

        Route::middleware('can:tasks.show')->whereNumber(['id', 'item'])->group(function () {
            Route::post('{id}/comments', [TaskDetailController::class, 'storeComment'])->name('comments.store');
            Route::delete('{id}/comments/{item}', [TaskDetailController::class, 'destroyComment'])->name('comments.destroy');
            Route::post('{id}/time-logs', [TaskDetailController::class, 'storeTimeLog'])->name('time-logs.store');
            Route::delete('{id}/time-logs/{item}', [TaskDetailController::class, 'destroyTimeLog'])->name('time-logs.destroy');
            Route::post('{id}/attachments', [TaskDetailController::class, 'storeAttachment'])->name('attachments.store');
            Route::get('{id}/attachments/{item}', [TaskDetailController::class, 'downloadAttachment'])->name('attachments.download');
            Route::delete('{id}/attachments/{item}', [TaskDetailController::class, 'destroyAttachment'])->name('attachments.destroy');
        });

        Route::middleware('can:tasks.edit')->whereNumber(['id', 'item'])->group(function () {
            Route::post('{id}/dependencies', [TaskDetailController::class, 'storeDependency'])->name('dependencies.store');
            Route::delete('{id}/dependencies/{item}', [TaskDetailController::class, 'destroyDependency'])->name('dependencies.destroy');
        });
    });
});
