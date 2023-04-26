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
    protected string $name = 'start';
    
    /**
     * 指令说明
     *
     * @var string
     */
    protected string $description = '获取您能够操作的所有指令';
    
    /**
     * 操作的业务处理
     *
     * @return void
     */
    public function handle(): void
    {
        $keyboard = [
            ['/ERS [汇率] (汇率设置)'],
            ['/income [金额] (入账)'],
            ['/clearing [金额] (出账)'],
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