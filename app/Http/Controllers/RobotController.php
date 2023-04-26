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
            'url' => 'https://robot.southwan.cn/telegram/webhook'
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
        
        $this->telegram->sendMessage([
            'chat_id' => '5835484544',
            'text' => 'Hello World'
        ]);
        
        $keyboard = [
            ['7', '8', '9'],
            ['4', '5', '6'],
            ['1', '2', '3'],
            ['0']
        ];
        
        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
        
        $response = $this->telegram->sendMessage([
            'chat_id' => '-969934184',
            'text' => 'Hello World',
            'reply_markup' => $reply_markup
        ]);
        
        
        return [$response];
    }
    
}