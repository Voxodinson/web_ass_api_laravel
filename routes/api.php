<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\SocialMediaController;
use App\Http\Controllers\DashboardController;
// user
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'create']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::put('/update/{id}', [UserController::class, 'update']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    // Other routes...
});

//product management
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});

//public products
Route::prefix('public')->group(function () {
    Route::get('/user/products', [ProductController::class, 'index']);
    Route::get('/user/products/{id}', [ProductController::class, 'show']);
    
    Route::get('/companies', [CompanyController::class, 'index']);

    Route::get('/companies/{id}', [CompanyController::class, 'show']);
    Route::get('/feedbacks', [FeedbackController::class, 'index']);

    Route::get('/orders/user/{userId}', [OrderController::class, 'getByUser']);
    Route::get('/social', [SocialMediaController::class, 'index']);
});

//company management
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);
    Route::put('/companies/{id}', [CompanyController::class, 'update']);
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);
});

//order management
Route::middleware('auth:sanctum')->group(function () {
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::put('orders/{id}', [OrderController::class, 'update']);
    Route::get('/orders/user/{userId}', [OrderController::class, 'getByUser']);
    Route::delete('orders/{id}', [OrderController::class, 'destroy']);
});

//feedback
Route::middleware('auth:sanctum')->group(function () {
    Route::get('feedbacks', [FeedbackController::class, 'index']);
    Route::post('feedbacks', [FeedbackController::class, 'store']);
    Route::get('feedbacks/{id}', [FeedbackController::class, 'show']);
    Route::put('feedbacks/{id}', [FeedbackController::class, 'update']);
    Route::delete('feedbacks/{id}', [FeedbackController::class, 'destroy']);
});

//social media
Route::middleware('auth:sanctum')->group(function () {
    Route::get('social', [SocialMediaController::class, 'index']);
    Route::post('social', [SocialMediaController::class, 'store']);
    Route::get('social/{id}', [SocialMediaController::class, 'show']);
    Route::put('social/{id}', [SocialMediaController::class, 'update']);
    Route::delete('social/{id}', [SocialMediaController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
});