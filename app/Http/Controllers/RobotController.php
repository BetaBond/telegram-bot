<?php

namespace App\Http\Controllers;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

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
        $response = $this->telegram->getMe();
        
        $this->telegram->sendMessage([
            'chat_id' => '@southwan',
            'text' => 'Hello World'
        ]);
        
        return [$response];
    }
    
}