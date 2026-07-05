<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PackerOrderController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:admin')->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::apiResource('orders', OrderController::class);

        Route::post('/orders/{order}/ready-to-ship', [OrderController::class, 'markReadyToShip']);
        Route::post('/orders/{order}/shipped', [OrderController::class, 'markShipped']);
    });

    Route::middleware('role:packer')->group(function () {
        Route::get('/packer/orders', [PackerOrderController::class, 'index']);
        Route::get('/packer/orders/{order}', [PackerOrderController::class, 'show']);
        Route::post('/packer/orders/{order}/start', [PackerOrderController::class, 'start']);
        Route::post('/packer/order-items/{item}/confirm', [PackerOrderController::class, 'confirmItem']);
        Route::post('/packer/orders/{order}/photos', [PackerOrderController::class, 'uploadPhoto']);
        Route::post('/packer/orders/{order}/complete', [PackerOrderController::class, 'complete']);
        Route::post('/packer/orders/{order}/issue', [PackerOrderController::class, 'flagIssue']);
    });
});
