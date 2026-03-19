<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PropertyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform API Routes
|--------------------------------------------------------------------------
|
| These routes serve as the shared platform API consumed by:
| - Booking engine (guest-facing widget)
| - Channel manager (Airbnb, VRBO, Booking.com sync)
| - Owner portal (property management dashboard)
| - Operations tools (internal admin)
|
| In production, all routes below would sit behind auth:sanctum.
| For this demo, read endpoints are open so the Angular dashboard works
| without token setup. Write endpoints simulate the auth boundary.
|
*/

Route::prefix('v1')->group(function () {

    // Health check (used by ALB target group)
    Route::get('/health', fn () => response()->json(['status' => 'ok']));

    // --- Public / Dashboard read endpoints ---
    Route::post('/availability/check', [AvailabilityController::class, 'check']);

    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/properties/{property}', [PropertyController::class, 'show']);

    Route::get('/bookings', [BookingController::class, 'index']);

    // --- Write endpoints (would be auth:sanctum in production) ---
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::put('/properties/{property}', [PropertyController::class, 'update']);
    Route::delete('/properties/{property}', [PropertyController::class, 'destroy']);
    Route::post(
        '/properties/{property}/regenerate-description',
        [PropertyController::class, 'regenerateDescription'],
    );

    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    // AI-powered features
    Route::post('/ai/guest-response', [AIController::class, 'guestResponse']);
    Route::post(
        '/ai/properties/{property}/pricing-suggestion',
        [AIController::class, 'pricingSuggestion'],
    );

    // AI Agents (orchestrated, with guardrails + evaluation)
    Route::post('/agents/chat', [AgentController::class, 'chat']);
    Route::get('/agents/{agent}/stats', [AgentController::class, 'stats']);
});
