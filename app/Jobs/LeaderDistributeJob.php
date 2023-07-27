<?php

namespace App\Jobs;

use App\Helpers\MessageHelper;
use App\Models\Robots;
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
 * 管理机器人命令分发
 *
 * @author beta
 */
class LeaderDistributeJob implements ShouldQueue
{
    
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * 创建一个 job 实例
     *
     * @param  string  $token
     * @param  array  $info
     * @param  string  $command
     * @param  array  $params
     */
    public function __construct(
        private string $token,
        private array $info,
        private string $command,
        private array $params
    ) {
        //
    }
    
    /**
     * 执行这个任务
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $telegram = new Api(
                $this->token,
                baseBotUrl: config('telegram.base_bot_url'),
            );
            
            Log::info('处理: '.$telegram->getMe()->id);
        } catch (TelegramSDKException $e) {
            Log::error('LeaderDistributeJob('.__LINE__.'): '.$e->getMessage());
            return;
        }
        
        $message = 'Job';
        $message = MessageHelper::compatibleParsingMd2($message);
        
        if ($message) {
            try {
                $telegram->sendMessage([
                    'chat_id'    => $this->info['chat_id'],
                    'parse_mode' => 'MarkdownV2',
                    'text'       => $message
                ]);
            } catch (TelegramSDKException $e) {
                Log::error('LeaderDistributeJob('.__LINE__.'): '.$e->getMessage());
            }
        }
    }
    
    /**
     * 处理失败处理
     *
     * @param  Throwable  $e
     */
    public function failed(Throwable $e): void
    {
        Log::error('LeaderDistributeJob: '.$e->getMessage());
    }
    
}
