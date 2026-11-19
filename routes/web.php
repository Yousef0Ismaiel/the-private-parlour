<?php

use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EntryController;
use App\Http\Middleware\RequireSecondFactor;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('/login', 'access.login')->name('login');
    Route::post('/login', [EntryController::class, 'password'])->middleware('throttle:password');
    Route::get('/verify', [EntryController::class, 'challenge']);
    Route::post('/verify', [EntryController::class, 'verify'])->middleware('throttle:verification');
    Route::view('/join', 'access.join');
    Route::post('/join', [EnrollmentController::class, 'redeem'])->middleware('throttle:invitation');
    Route::get('/pair', [EnrollmentController::class, 'form']);
    Route::post('/pair', [EnrollmentController::class, 'finish'])->middleware('throttle:verification');
});

Route::middleware(['auth', RequireSecondFactor::class])->group(function () {
    Route::view('/', 'parlour')->name('home');
    Route::get('/edition/cover.webp', fn () => response()->file(resource_path('private/bouquet.webp'), [
        'Content-Type' => 'image/webp',
    ]));
    Route::get('/edition/print.css', fn () => response()->file(resource_path('private/print.css'), [
        'Content-Type' => 'text/css; charset=UTF-8',
    ]));
});
Route::post('/logout', [EntryController::class, 'leave']);
