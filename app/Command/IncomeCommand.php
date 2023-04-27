<?php

namespace App\Command;

use Telegram\Bot\Commands\Command;

/**
 * 入账命令
 *
 * @author southwan
 */
class IncomeCommand extends Command
{
    
    /**
     * 指令名称
     *
     * @var string
     */
    protected string $name = 'clearing';
    
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
        $fallback = $this->getUpdate()->getMessage();
        
        $this->replyWithMessage([
            'text' => '设置成功！'.json_encode($fallback, JSON_UNESCAPED_UNICODE),
        ]);
    }
    
}