<?php

namespace App\Helpers;

/**
 * 消息助手
 *
 * @author southwan
 */
class MessageHelper
{
    
    /**
     * 兼容解析 MarkdownV2 语法
     *
     * @param  string  $message
     * @return string
     */
    public static function compatibleParsingMd2(string $message): string
    {
        $message = str_replace('.', "\\.", $message);
        $message = str_replace('-', "\\-", $message);
        $message = str_replace('=', "\\=", $message);
        $message = str_replace('[', "\\[", $message);
        $message = str_replace(']', "\\]", $message);
        $message = str_replace('|', "\\|", $message);
        $message = str_replace('(', "\\(", $message);
        $message = str_replace('!', "\\!", $message);
        $message = str_replace('_', "\\_", $message);
        $message = str_replace('{', "\\{", $message);
        $message = str_replace('}', "\\}", $message);
        
        return str_replace(')', "\\)", $message);
    }
    
    /**
     * 验证参数是否符合
     *
     * @param  array  $params
     * @param  int  $num
     * @return bool|string
     */
    public static function parameterCalibration(
        array $params,
        int $num
    ): bool|string {
        if (empty($params)) {
            return '参数错误';
        }
        
        if (count($params) !== $num) {
            return '参数不足';
        }
        
        return true;
    }
    
    
}