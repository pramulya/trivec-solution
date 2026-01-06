<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Termii Webhook
Route::post('/termii/webhook', [\App\Http\Controllers\SmsController::class, 'handleWebhook']);
