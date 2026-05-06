<?php

use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CheatController;
use App\Http\Controllers\ParticipantQuizController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [RegisterController::class, 'register'])->name('register');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1')->name('login');
    Route::delete('/logout', [LogoutController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::post('/change-password', [ChangePasswordController::class, 'changePassword'])->middleware('auth:sanctum', 'throttle:6,1')->name('change.password');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'forgot'])->middleware('throttle:6,1')->name('forgot.password');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->middleware('throttle:6,1')->name('reset.password');
    Route::get('/me', [MeController::class, 'me'])->middleware('auth:sanctum')->name('me');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('quizzes', QuizController::class);
    Route::get('quizzes/{quiz}/questions', [QuestionController::class, 'indexByQuiz']);
    Route::post('quizzes/{quiz}/questions', [QuestionController::class, 'store']);
    Route::apiResource('questions', QuestionController::class)->except('store');
    Route::get('quizzes/{quiz}/cheats/summary', [CheatController::class, 'summary']);
    Route::get('participants/{participant}/cheats', [CheatController::class, 'indexByParticipant']);
});
Route::get('quizzes/join-quiz/{password}', [QuizController::class, 'findQuizByPassword']);
Route::post('quizzes/{quiz}/submit', [ParticipantQuizController::class, 'submit'])
    ->middleware('throttle:30,1');
Route::get('quizzes/{quiz}/submission', [ParticipantQuizController::class, 'showSubmission']);
Route::post('cheats', [CheatController::class, 'store']);
