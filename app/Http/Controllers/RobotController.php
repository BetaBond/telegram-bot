<?php

namespace App\Http\Controllers;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * 机器人控制器
 *
 * @author southwan
 */
class RobotController
{
    
    /**
     * 设置 [WebHook]
     *
     * @return array
     */
    public function webhook(): array
    {
        $response = Telegram::setWebhook([
            'url' => 'https://robot.southwan.cn/api/telegram/webhook/messages'
        ]);
        
        return [$response];
    }
    
    /**
     * @throws TelegramSDKException
     */
    public function getMe(): array
    {
        $telegram = new Api(
            '5633755818:AAGMWla-Pk96w97X_uDv9_7ZVQcH7l6v45I',
            baseBotUrl: config('telegram.base_bot_url'),
        );
        
        $response = $telegram->getMe();
        
        return [$response];
    }
    
}