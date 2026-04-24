<?php

use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [RegisterController::class, 'register'])->name('register');

    Route::get('/email/verify/{id}/{hash}', [RegisterController::class, 'verify'])
        ->middleware(['auth:sanctum', 'signed'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [RegisterController::class, 'sendVerificationEmail'])
        ->middleware(['auth:sanctum', 'throttle:6,1'])
        ->name('verification.send');

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum')->name('user');
});
