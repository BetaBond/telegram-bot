<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
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
        
        $host = $_SERVER['HTTP_HOST'];
        
        $response = Telegram::setWebhook([
            'url' => "https://$host/api/telegram/webhook/message/$token"
        ]);
        
        return [$response];
    }
    
}