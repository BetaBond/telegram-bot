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
    public static function compatible_parsing_md2(string $message): string
    {
        $message = str_replace('.', "\\.", $message);
        $message = str_replace('-', "\\-", $message);
        $message = str_replace('=', "\\=", $message);
        $message = str_replace('[', "\\[", $message);
        $message = str_replace(']', "\\]", $message);
        $message = str_replace('|', "\\|", $message);
        $message = str_replace('(', "\\(", $message);
        
        return str_replace(')', "\\)", $message);
    }
    
}