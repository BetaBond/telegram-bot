<?php

use Illuminate\Support\Facades\Http;
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

Route::any('/', function () {
    $response = Http::get(
        'https://www.okx.com/v3/c2c/tradingOrders/mostUsedPaymentAndBestPriceAds',
        [
            't' => time().'000',
            'cryptoCurrency' => 'USDT',
            'fiatCurrency' => 'CNY',
            'side' => 'buy',
        ]
    );
    
    if (!$response->successful()) {
        return '获取失败！';
    }
    
    return $response->json();
});