<?php

namespace App\Jobs\Leader;

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
 * 加入主网命令
 *
 * @author beta
 */
class Join implements ShouldQueue
{
    
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * 创建一个 job 实例
     *
     * @return void
     */
    public function __construct(
        private string $token,
        private array $info,
        private array $params
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
        $parameterCalibration = MessageHelper::parameterCalibration(
            $this->params,
            1
        );
        
        if ($parameterCalibration !== true) {
            $this->send($parameterCalibration);
            return;
        }
        
        $token = $this->params[0];
        
        try {
            $telegram = new Api(
                $token,
                baseBotUrl: config('telegram.base_bot_url'),
            );
            
            $robot = $telegram->getMe();
            $removeWebhook = $telegram->removeWebhook();
            
            $url = config('telegram.webhook_url');
            $url = "$url/$token";
            
            $webHook = $telegram->setWebhook([
                'url' => $url
            ]);
            
            if (!$webHook || !$removeWebhook) {
                $this->send('订阅到主网时发生异常');
                return;
            }
            
            $exists = Robots::query()
                ->where(RobotsTrace::TOKEN, $token)
                ->where(RobotsTrace::T_UID, $robot->id)
                ->exists();
            
            if ($exists) {
                $this->send('该机器人已经加入主网，请勿重复加入！');
                return;
            }
            
            $model = Robots::query()->create([
                RobotsTrace::TOKEN                  => $token,
                RobotsTrace::T_UID                  => $robot->id,
                RobotsTrace::USERNAME               => $robot->username,
                RobotsTrace::EXPIRE_AT              => time(),
                RobotsTrace::INCOME_EXCHANGE_RATE   => 0,
                RobotsTrace::CLEARING_EXCHANGE_RATE => 0,
                RobotsTrace::INCOMING_RATE          => 0,
                RobotsTrace::CLEARING_RATE          => 0,
            ]);
            
            if (!$model->save()) {
                $this->send('创建失败!');
                return;
            }
            
            $this->send(implode("\n", [
                "*成功将机器人加入到主网！*",
                "Telegram UID :  `$robot->id`",
                "Telegram Username :  `$robot->username`"
            ]));
        } catch (TelegramSDKException $e) {
            Log::error(__CLASS__.'('.__LINE__.')'.': '.$e->getMessage());
        }
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