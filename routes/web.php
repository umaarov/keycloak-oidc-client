<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('auth.login');
})->name('login');

Route::get('/auth/redirect', [AuthController::class, 'redirectToProvider'])->name('login.keycloak');
Route::get('/auth/callback', [AuthController::class, 'handleProviderCallback']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function() {
        return view('dashboard');
    })->name('dashboard');
});
