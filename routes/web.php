<?php

use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\HouseholdInvitationController;
use App\Http\Middleware\ProtectInvitationResponse;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware([ProtectInvitationResponse::class, 'auth', 'verified'])->group(function () {
    Route::get('/household-invitations/{token}', [HouseholdInvitationController::class, 'show'])
        ->name('household.invitations.show');
    Route::post('/household-invitations/accept', [HouseholdInvitationController::class, 'accept'])
        ->name('household.invitations.accept');
});

Route::post('/households', [HouseholdController::class, 'store'])->middleware('auth')->name('households.store');
Route::post('/households/{household}/activate', [HouseholdController::class, 'activate'])->middleware('auth')->name(
    'households.activate'
);
Route::post('/households/{household}/invitations', [HouseholdController::class, 'storeInvitation'])->middleware('auth')
    ->name('household.invitations.store');

require __DIR__.'/settings.php';
