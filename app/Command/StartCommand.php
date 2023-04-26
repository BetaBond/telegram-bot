<?php

namespace App\Command;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;

/**
 * 开始指令
 *
 * @author southwan
 */
class StartCommand extends Command
{
    
    /**
     * 指令名称
     *
     * @var string
     */
    protected string $name = '开始';
    
    /**
     * 指令说明
     *
     * @var string
     */
    protected string $description = 'Start Command to get you started';
    
    /**
     * 操作的业务处理
     *
     * @return void
     */
    public function handle(): void
    {
        $keyboard = [
            ['/汇率设置'],
            ['/进账'],
            ['/出账'],
        ];
        
        $reply_markup = Keyboard::make([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
        
        $this->replyWithMessage([
            'text' => '请选择需要执行的指令！',
            'reply_markup' => $reply_markup,
        ]);
    }
    
}