<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:property_specialist,receptionist'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::view(
            '/',
            'dashboard-placeholder',
            ['portal' => 'Property Specialist or Receptionist']
        )
            ->name('dashboard');
    });
