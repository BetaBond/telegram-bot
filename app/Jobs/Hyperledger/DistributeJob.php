<?php

namespace App\Jobs\Hyperledger;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

use App\Jobs\Hyperledger\Wallet\Create as CreateWallet;
use App\Jobs\Hyperledger\Wallet\Mine as MineWallet;
use App\Jobs\Hyperledger\Wallet\Balance as BalanceWallet;
use App\Jobs\Hyperledger\Wallet\Name as NameWallet;

/**
 * 超级账本机器人命令分发
 *
 * @author beta
 */
class DistributeJob implements ShouldQueue
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
            '单价' => Price::dispatch($this->token, $this->info),
            '检查授权' => InspectionAuth::dispatch($this->token, $this->info),
            default => false,
        };

        if ($noAuth !== false) {
            return;
        }

        match ($this->command) {
            '创建钱包' => CreateWallet::dispatch(
                $this->token,
                $this->info,
                $this->params
            ),
            '我的钱包' => MineWallet::dispatch($this->token, $this->info),
            '钱包余额' => BalanceWallet::dispatch(
                $this->token,
                $this->info,
                $this->params
            ),
            '钱包名称' => NameWallet::dispatch(
                $this->token,
                $this->info,
                $this->params
            ),
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
        Log::error(__CLASS__.'('.__LINE__.')'.': '.$e->getMessage());
    }

}
