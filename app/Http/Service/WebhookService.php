<?php

namespace App\Http\Service;

use Illuminate\Support\Facades\Cache;

class WebhookService
{
    
    /**
     * 说明指令
     *
     * @return string
     */
    public static function explain(): string
    {
        return implode("\n", [
            "*使用说明：*",
            "1: 每个用户在会话（`群聊/私聊`）中每秒最多受理一次指令",
            "2: 每个指令需要带上对应的参数以空格进行分割",
            "3: 所有指令消息都会被保护，所以无法转发或复制"
        ]);
    }
    
    /**
     * 帮助指令
     *
     * @return string
     */
    public static function help(): string
    {
        return implode("\n", [
            "*指令使用帮助：*",
            "`说明`  \\|  在当前版本的使用说明",
            "`帮助`  \\|  在当前版本的使用帮助（指令列表）",
            "`汇率`  \\|  设置当前的汇率 \\| 汇率 \\[汇率\\|小数\\]",
            "`进账`  \\|  设置当前进账金额 \\| 进账 \\[金额\\|小数\\]",
            "`出账`  \\|  设置当前出账金额 \\| 出账 \\[金额\\|小数\\]",
        ]);
    }
    
    /**
     * 设置汇率信息
     *
     * @param  array  $params
     * @return string
     */
    public static function exchangeRate(array $params): string
    {
        if (empty($params)) {
            return "参数错误";
        }
        
        if (!is_numeric($params[0])) {
            return "参数类型错误";
        }
        
        $exchangeRate = (float) $params[0];
        $cache = Cache::put('exchange_rate', $exchangeRate);
        
        if ($cache) {
            $exchangeRate = Cache::get('exchange_rate');
            return implode("\n", [
                "*汇率设置成功！！！*",
                "当前汇率为：`$exchangeRate`"
            ]);
        }
        
        return "失败";
    }
    
    public static function income(array $params): string
    {
        return "";
    }
    
    public static function clearing(array $params): string
    {
        return "";
    }
    
}