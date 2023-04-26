<?php

namespace App\Command;

use Telegram\Bot\Commands\Command;

/**
 * 出账命令
 *
 * @author southwan
 */
class ClearingCommand extends Command
{
    
    /**
     * 指令名称
     *
     * @var string
     */
    protected string $name = 'income';
    
    /**
     * 指令说明
     *
     * @var string
     */
    protected string $description = '设置入账信息';
    
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