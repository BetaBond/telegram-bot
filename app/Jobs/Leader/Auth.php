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
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;

/**
 * 授权命令
 *
 * @author beta
 */
class Auth implements ShouldQueue
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
            2
        );
        
        if ($parameterCalibration !== true) {
            $this->send($parameterCalibration);
            return;
        }
        
        $robot_id = $this->params[1];
        $t_uid = $this->params[0];
        
        $exists = Robots::query()
            ->where(RobotsTrace::T_UID, $robot_id)
            ->exists();
        
        if (!$exists) {
            $this->send('机器人不存在于主网中!(ROBOT_ID: '.$robot_id.')');
            return;
        }
        
        $exists = \App\Models\Auth::query()
            ->where(AuthTrace::T_UID, $t_uid)
            ->where(AuthTrace::ROBOT_ID, $robot_id)
            ->exists();
        
        if ($exists) {
            $this->send('该账号已授权此机器人，请勿重复授权!');
            return;
        }
        
        $model = \App\Models\Auth::query()->create([
            AuthTrace::T_UID    => $t_uid,
            AuthTrace::ROBOT_ID => $robot_id,
        ]);
        
        
        $message = $model ? '授权成功' : '授权失败';
        $this->send($message);
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