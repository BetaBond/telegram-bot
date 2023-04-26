<?php

use App\Http\Controllers\RobotController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

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
        Route::any('show', 'show');
        
    });
    
});