<?php

namespace App\Command;

use Telegram\Bot\Commands\Command;

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
    protected string $description = 'Start Command to get you started';
    
    /**
     * 操作的业务处理
     *
     * @return void
     */
    public function handle(): void
    {
        $this->replyWithLocation([
            'text' => 'Hey, there! Welcome to our bot!',
        ]);
    }
    
}