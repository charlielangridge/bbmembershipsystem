<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('account/{account}', [AccountController::class, 'show'])->name('account.show');
});

require __DIR__.'/settings.php';
