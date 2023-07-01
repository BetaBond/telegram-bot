<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;

/**
 * 数据同步控制器
 *
 * @author southwan
 */
class SyncController
{
    
    /**
     * 最优价格同步
     *
     * @param  Request  $request
     *
     * @return string
     */
    public function price(Request $request): string
    {
        $requestParams = $request::validate([
            'side'      => [
                'required',
                'string',
                Rule::in(['buy', 'sell']),
            ],
            'payment'   => [
                'required',
                'string',
                Rule::in(['bank', 'wxPay', 'aliPay']),
            ],
            'unitPrice' => [
                'required',
                'numeric',
                'min:0'
            ],
        ]);
        
        $cache = Cache::store('redis')->put(
            'best_price_'.$requestParams['side'].'_'.$requestParams['payment'],
            $requestParams['unitPrice']
        );
        
        return $cache ? '成功' : '失败';
    }
    
}