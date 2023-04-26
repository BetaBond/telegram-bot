<?php

namespace App\Http\Controllers;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Keyboard\Keyboard;
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
     * 设置 [WebHook]
     *
     * @return array
     */
    public function webhook(): array
    {
        $response = Telegram::setWebhook([
            'url' => 'https://robot.southwan.cn/api/telegram/webhook/message'
        ]);
        
        return [$response];
    }
    
    /**
     * 显示机器人信息
     *
     * @return array
     * @throws TelegramSDKException
     */
    public function show(): array
    {
        $response = $this->telegram->getMe();
        
        return [$response];
    }
    
}