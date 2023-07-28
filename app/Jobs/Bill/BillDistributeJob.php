<?php

namespace App\Jobs\Bill;

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
class BillDistributeJob implements ShouldQueue
{
    
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
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
        // 无需验证的指令分发
        $noAuth = match ($this->command) {
            '我的' => Mine::dispatch($this->token, $this->info),
            default => false,
        };
        
        if ($noAuth !== false) {
            return;
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
