<?php

use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [WorkflowController::class, 'dashboard'])->name('dashboard');
    Route::resource('workflows', WorkflowController::class)->except(['create', 'edit']);
    Route::patch('workflows/{workflow}/rename', [WorkflowController::class, 'rename'])->name('workflows.rename');
    Route::post('workflows/{workflow}/duplicate', [WorkflowController::class, 'duplicate'])->name('workflows.duplicate');
    Route::post('workflows/{workflow}/archive', [WorkflowController::class, 'archive'])->name('workflows.archive');
    Route::post('workflows/{workflow}/unarchive', [WorkflowController::class, 'unarchive'])->name('workflows.unarchive');
    Route::post('workflows/{workflow}/restore', [WorkflowController::class, 'restore'])->withTrashed()->name('workflows.restore');
    Route::delete('workflows/{workflow}/force', [WorkflowController::class, 'forceDelete'])->withTrashed()->name('workflows.force-delete');
});

require __DIR__.'/settings.php';
