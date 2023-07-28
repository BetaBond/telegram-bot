<?php

namespace App\Jobs\Bill;

use App\Helpers\MessageHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;

/**
 * 单价命令
 *
 * @author beta
 */
class Price implements ShouldQueue
{
    
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * 创建一个 job 实例
     *
     * @return void
     */
    public function __construct(
        private string $token,
        private array $info
    ) {
        //
    }
    
    /**
     * 发送消息
     *
     * @param  string  $messages
     *
     * @return void
     */
    private function send(string $messages): void
    {
        $messages = MessageHelper::compatibleParsingMd2($messages);
        
        try {
            MessageHelper::send($this->token, [
                'chat_id'    => $this->info['chat_id'],
                'parse_mode' => 'MarkdownV2',
                'text'       => $messages
            ]);
        } catch (TelegramSDKException $e) {
            Log::error(__CLASS__.'('.__LINE__.')'.': '.$e->getMessage());
        }
    }
    
    /**
     * 执行这个任务
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $store = Cache::store('redis')->get('okx_usdt_block_trade');
            
            $timestamp = Cache::store(
                'redis'
            )->get('okx_usdt_block_trade_updated');
            
            $time = empty($timestamp) ? '未同步'
                : date('Y-m-d H:i:s', $timestamp);
            
        } catch (InvalidArgumentException $e) {
            Log::error(__CLASS__.'('.__LINE__.')'.': '.$e->getMessage());
            $this->send('单价信息读取错误!');
            return;
        }
        
        $messages = ["*当前欧易大宗商品买卖价格：*"];
        $messages[] = '';
        $messages[] = '数据同步时间：';
        $messages[] = "[`$time`]";
        $messages[] = '';
        $messages[] = "*买入方向(TOP10)：*";
        $messages[] = '';
        
        $prices = json_decode($store, true);
        $prices = is_array($prices) ? $prices : [];
        
        foreach ($prices as $key => $price) {
            $messages[] = "[`".($key + 1)."`]\t\t:\t\t`￥$price`";
        }
        
        $this->send(implode("\n", $messages));
    }
    
    /**
     * 处理失败处理
     *
     * @param  Throwable  $e
     */
    public function failed(Throwable $e): void
    {
        Log::error(__CLASS__.'('.__LINE__.')'.': '.$e->getMessage());
    }
    
}