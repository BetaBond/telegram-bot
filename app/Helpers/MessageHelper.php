<?php

namespace App\Helpers;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

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
     *
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
     *
     * @return bool|string
     */
    public static function parameterCalibration(
        array $params,
        int $num
    ): bool|string {
        if (empty($params)) {
            return '参数错误';
        }

        if (count($params) < $num) {
            return '参数不足';
        }

        return true;
    }

    /**
     * 发送消息
     *
     * @param  string  $token
     * @param  array  $params
     *
     * @return void
     * @throws TelegramSDKException
     */
    public static function send(string $token, array $params): void
    {
        $telegram = new Api(
            $token,
            baseBotUrl: config('telegram.base_bot_url'),
        );

        $telegram->sendMessage($params);
    }

}
