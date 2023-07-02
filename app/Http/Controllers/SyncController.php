<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

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
            'price' => ['required', 'string', 'json'],
        ]);
        
        $okx_usdt_block_trade = Cache::store('redis')->put(
            'okx_usdt_block_trade',
            $requestParams['price']
        );
        
        $okx_usdt_block_trade_updated = Cache::store('redis')->put(
            'okx_usdt_block_trade_updated',
            time()
        );
        
        $cache = $okx_usdt_block_trade && $okx_usdt_block_trade_updated;
        
        return $cache ? '成功' : '失败';
    }
    
}