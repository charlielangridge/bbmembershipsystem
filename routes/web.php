<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPhotoController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::get('members', [MemberController::class, 'index'])->name('members.index');
Route::get('members/{member}', [MemberController::class, 'show'])->name('members.show');
Route::get('members/{member}/photo', MemberPhotoController::class)->name('members.photo');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('account/{account}', [AccountController::class, 'show'])->name('account.show');
});

require __DIR__.'/settings.php';
