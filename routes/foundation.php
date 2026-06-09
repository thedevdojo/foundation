<?php

use Devdojo\Foundation\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'view-foundation-setup'])->group(function () {
    Route::get('foundation/setup', [SetupController::class, 'index'])->name('foundation.setup');
    Route::post('foundation/setup', [SetupController::class, 'update'])->name('foundation.setup.update');
});

// Convenience redirect to the setup screen.
Route::redirect('foundation', 'foundation/setup');
