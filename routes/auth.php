<?php

use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
| No self-registration: DocFlow accounts are provisioned by an administrator
| (§12 lists login / forgot / reset / profile only, and §29 makes user
| creation an admin responsibility).
*/
Route::middleware('guest')->group(function () {
    Volt::route('login', 'pages.auth.login')
        ->name('login');

    Volt::route('forgot-password', 'pages.auth.forgot-password')
        ->name('password.request');

});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'pages.auth.verify-email')
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');

    // Where EnsurePasswordIsChanged parks an account that signed in with a
    // mailed temporary password, until it chooses a real one (§4).
    Volt::route('change-password', 'pages.auth.change-password')
        ->name('password.change');
});
