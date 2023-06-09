<?php

namespace App\Http\Controllers;

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
        $key = config('telegram.default');
        $token = config("telegram.bots.$key.token");
        
        $host = $_SERVER['host'];
        
        $response = Telegram::setWebhook([
            'url' => "https://$host/api/telegram/webhook/message/$token"
        ]);
        
        return [$response];
    }
    
}