<?php

namespace App\Command;

use Telegram\Bot\Commands\Command;

/**
 * 汇率指令
 *
 * @author southwan
 */
class ERSCommand extends Command
{
    
    /**
     * 指令名称
     *
     * @var string
     */
    protected string $name = 'ERS';
    
    /**
     * 指令说明
     *
     * @var string
     */
    protected string $description = '设置当前的结算汇率';
    
    /**
     * 操作的业务处理
     *
     * @return void
     */
    public function handle(): void
    {
        $this->replyWithMessage([
            'text' => '设置成功！',
        ]);
    }
    
}