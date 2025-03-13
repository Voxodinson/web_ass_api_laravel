<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// user
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'create']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/update/{id}', [UserController::class, 'update']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    // Other routes...
});
