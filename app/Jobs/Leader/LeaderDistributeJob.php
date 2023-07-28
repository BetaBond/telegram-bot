<?php

namespace App\Jobs\Leader;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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
     * 授权允许ID
     *
     * @var array
     */
    const AUTH = [868447518, 5448144972];
    
    /**
     * 创建一个 job 实例
     *
     * @param  string  $token
     * @param  array  $info
     * @param  string  $command
     * @param  array  $params
     *
     * @return void
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
        // 授权验证
        if (!in_array(
            $this->info['form_id'],
            self::AUTH)
        ) {
            return;
        }
        
        // 分发任务
        match ($this->command) {
            '说明' => Explain::dispatch($this->token, $this->info),
            '帮助' => Help::dispatch($this->token, $this->info),
            '加入' => Join::dispatch($this->token, $this->info, $this->params),
            '授权' => Auth::dispatch($this->token, $this->info, $this->params),
            default => false,
        };
    }
    
    /**
     * 处理失败处理
     *
     * @param  Throwable  $e
     */
    public function failed(Throwable $e): void
    {
        Log::error(json_encode([$this->token, $this->command]));
        Log::error(__CLASS__.'('.__LINE__.')'.': '.$e->getMessage());
    }
    
}
