<?php

namespace App\Http\Controllers;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\User;

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
     * @return User|string
     */
    public function show(): User|string
    {
        try {
            $response = $this->telegram->getMe();
        } catch (TelegramSDKException $e) {
            return $e->getMessage();
        }
        
        return $response;
    }
    
}