<?php

namespace App\Http\Service;

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
            "2: 每个指令需要带上对应的参数以空格进行分割"
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
            "`说明` 在当前版本的使用说明",
            "`帮助` 在当前版本的使用帮助（指令列表）",
            "`汇率` 设置当前的汇率 \\| 汇率 `汇率\\|小数`"
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
        return "";
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