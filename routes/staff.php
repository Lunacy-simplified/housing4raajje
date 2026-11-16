<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Staff\ApplicationController;
use App\Http\Controllers\Staff\ContactController;
use App\Http\Controllers\Staff\EscalationController;
use App\Http\Controllers\Staff\InquiryController;
use App\Http\Controllers\Staff\LeaseController;
use App\Http\Controllers\Staff\MaintenanceController;
use App\Http\Controllers\Staff\PaymentController;
use App\Http\Controllers\Staff\PropertyController;
use App\Http\Controllers\Staff\TicketController;
use App\Http\Controllers\Staff\ViewingController;
use Illuminate\Support\Facades\Route;

// Branch work. Everything here is limited to the signed-in staff member's own branch.
Route::middleware(['auth', 'role:property_specialist,receptionist,branch_manager'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'staff'])->name('dashboard');

        Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
        Route::get('/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/viewings', [ViewingController::class, 'index'])->name('viewings.index');
        Route::patch('/viewings/{viewing}/schedule', [ViewingController::class, 'schedule'])->name('viewings.schedule');
        Route::get('/leases', [LeaseController::class, 'index'])->name('leases.index');
        Route::get('/leases/{lease}', [LeaseController::class, 'show'])->name('leases.show');
        Route::post('/leases/{lease}/payments', [PaymentController::class, 'store'])->name('leases.payments.store');

        Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::patch('/maintenance/{maintenance}', [MaintenanceController::class, 'update'])->name('maintenance.update');

        Route::get('/inquiries', [InquiryController::class, 'index'])->name('inquiries.index');
        Route::post('/inquiries', [InquiryController::class, 'store'])->name('inquiries.store');
        Route::post('/inquiries/{inquiry}/route', [InquiryController::class, 'route'])->name('inquiries.route');
        Route::patch('/inquiries/{inquiry}/close', [InquiryController::class, 'close'])->name('inquiries.close');
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::put('/contacts/{user}', [ContactController::class, 'update'])->name('contacts.update');

        Route::post('/escalations', [EscalationController::class, 'store'])->name('escalations.store');

        // Decisions that carry responsibility: specialists and managers only, not the front desk.
        Route::middleware('role:property_specialist,branch_manager')->group(function () {
            Route::patch('/properties/{property}/decision', [PropertyController::class, 'decide'])->name('properties.decide');
            Route::patch('/applications/{application}/decision', [ApplicationController::class, 'decide'])->name('applications.decide');
            Route::patch('/viewings/{viewing}/log', [ViewingController::class, 'log'])->name('viewings.log');
            Route::get('/applications/{application}/lease', [LeaseController::class, 'create'])->name('leases.create');
            Route::post('/applications/{application}/lease', [LeaseController::class, 'store'])->name('leases.store');
            Route::patch('/leases/{lease}/terminate', [LeaseController::class, 'terminate'])->name('leases.terminate');
        });
    });
