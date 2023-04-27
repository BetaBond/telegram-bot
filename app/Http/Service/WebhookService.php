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
        return "
        # 使用说明
        =============================================
        1. 每个用户在会话(群聊/私聊)中每秒最多受理一次指令
        2. 每个指令需要带上对应的参数以空格进行分割
        =============================================
        ";
    }
    
    /**
     * 帮助指令
     *
     * @return string
     */
    public static function help(): string
    {
        return "
                           指令帮助
        =============================================
        说明 | 获取当前版本的一些使用说明
        帮助 | 获取当前版本的指令使用指南
        汇率 | 设置当前的汇率(用于结算) | 汇率 [汇率|小数]
        进账 | 设置进账信息 | 进账 [金额|小数]
        出账 | 设置出账信息 | 出账 [金额|小数]
        =============================================
        ";
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