<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Owner\EarningsController;
use App\Http\Controllers\Owner\PropertyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'owner'])->name('dashboard');

        Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/properties/{property}', [PropertyController::class, 'show'])->whereNumber('property')->name('properties.show');
        Route::patch('/properties/{property}/availability', [PropertyController::class, 'availability'])->name('properties.availability');
        Route::delete('/properties/{property}/photos/{photo}', [PropertyController::class, 'photoDestroy'])->name('properties.photos.destroy');

        // Listing a property needs a verified email (auth spec 6.1).
        Route::middleware('verified')->group(function () {
            Route::get('/properties/create', [PropertyController::class, 'create'])->name('properties.create');
            Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store');
            Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
            Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
            Route::post('/properties/{property}/submit', [PropertyController::class, 'submit'])->name('properties.submit');
            Route::post('/properties/{property}/photos', [PropertyController::class, 'photoStore'])->name('properties.photos.store');
        });

        Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings.index');
        Route::get('/earnings/statement', [EarningsController::class, 'download'])->name('earnings.download');
    });
