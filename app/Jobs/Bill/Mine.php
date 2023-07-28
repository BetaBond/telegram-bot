<?php

namespace App\Jobs\Bill;

use App\Helpers\MessageHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;

/**
 * 我的命令
 *
 * @author beta
 */
class Mine implements ShouldQueue
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
        $formId = $this->info['form_id'];
        $formUserName = $this->info['form_user_name'];
        
        $this->send(implode("\n", [
            "*您的信息：*",
            "",
            'User Id\t\t\t\t\t\t\t\t\t\t\t\t:\t\t\t\t`'.$formId.'`',
            'User Name\t\t\t\t:\t\t\t\t`'.$formUserName.'`',
        ]));
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