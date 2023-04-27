<?php

namespace App\Command;

use Illuminate\Support\Facades\Cache;
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
        $fallback = $this->getUpdate()->getMessage();
        
        $text = $fallback['text'];
        $text = explode(' ', $text);
        
        if (!isset($text[1])) {
            $this->replyWithMessage([
                'text' => '汇率未填写',
            ]);
        }
        
        if (is_numeric($text[1])) {
            $this->replyWithMessage([
                'text' => '汇率必须为数值',
            ]);
        }
        
        $cache = Cache::put('exchange_rate', $text[1]);
        
        $this->replyWithMessage([
            'text' => $cache ? '设置成功！' : '设置失败！',
        ]);
    }
    
}