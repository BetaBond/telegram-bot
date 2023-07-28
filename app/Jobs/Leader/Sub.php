<?php

namespace App\Jobs\Leader;

use App\Helpers\MessageHelper;
use App\Models\Robots;
use App\Models\Trace\AuthTrace;
use App\Models\Trace\RobotsTrace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;

/**
 * 订阅命令
 *
 * @author beta
 */
class Sub implements ShouldQueue
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
        $robots = Robots::query()->get();
        $base_url = config('telegram.webhook_url');
        
        $tokenKey = RobotsTrace::TOKEN;
        $tUidKey = RobotsTrace::T_UID;
        
        foreach ($robots as $robot) {
            try {
                $telegram = new Api(
                    $robot->$tokenKey,
                    baseBotUrl: config('telegram.base_bot_url'),
                );
                
                $removeWebhook = $telegram->removeWebhook();
                $url = "$base_url/$robot->token";
                
                $webHook = $telegram->setWebhook([
                    'url' => $url
                ]);
                
                if (!$webHook || !$removeWebhook) {
                    $this->send('订阅失败(T_UID : '.$robot->$tUidKey.')');
                    continue;
                }
                
            } catch (TelegramSDKException $e) {
                $msg = '订阅失败(T_UID : '.$robot->$tUidKey.'): ';
                $msg .= $e->getMessage();
                $this->send($msg);
                continue;
            }
        }
        
        $this->send('全部订阅成功!');
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