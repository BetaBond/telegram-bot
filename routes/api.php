<?php

use App\Http\Controllers\RobotController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::prefix('/telegram')->group(function () {
    
    Route::controller(
        WebhookController::class
    )->prefix(
        'webhook'
    )->group(function () {
        
        Route::any('message', 'message');
        
    });
    
    Route::controller(
        RobotController::class
    )->prefix(
        'robot'
    )->group(function () {
        
        Route::any('webhook', 'webhook');
        
    });
    
});