<?php

use App\Http\Controllers\BookingController;
use App\Http\Middleware\VerifyJwt;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyJwt::class)->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
});
