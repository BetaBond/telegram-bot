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
    
    protected Api $telegram;
    
    /**
     * 创建一个新的控制器实例
     *
     * @param  Api  $telegram
     */
    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }
    
    /**
     * 显示机器人信息
     *
     * @return array
     * @throws TelegramSDKException
     */
    public function show(): array
    {
        $telegram = new Api(
            token: '5669756920:AAGO81biPNyd48fQsz_5vsGZ9NWMXhND8ps',
            baseBotUrl: 'telegram.southwan.cn/bot',
        );
        
        $response = $telegram->getMe();
        
        return [$response];
    }
    
}