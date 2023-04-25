<?php

namespace App\Http\Controllers;

use Telegram\Bot\Api;
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
     */
    public function show(): array
    {
//        $response = Telegram::getMe();
        
        return [$this->telegram];
    }
    
}