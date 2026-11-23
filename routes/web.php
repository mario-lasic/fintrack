<?php

use App\Http\Controllers\HouseholdController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::post('/households', [HouseholdController::class, 'store'])->middleware('auth')->name('households.store');
Route::post('/households/{household}/activate', [HouseholdController::class, 'activate'])->middleware('auth')->name(
    'households.activate'
);

require __DIR__ . '/settings.php';
