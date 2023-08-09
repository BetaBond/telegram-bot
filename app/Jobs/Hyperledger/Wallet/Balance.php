<?php

namespace App\Jobs\Hyperledger\Wallet;

use App\Helpers\MessageHelper;
use App\Models\Trace\WalletTrace;
use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;

/**
 * 钱包余额命令
 *
 * @author beta
 */
class Balance implements ShouldQueue
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
    public
    function handle(): void
    {
        $parameterCalibration = MessageHelper::parameterCalibration(
            $this->params,
            2
        );

        if ($parameterCalibration !== true) {
            $this->send($parameterCalibration);
            return;
        }

        $id = $this->params[0];
        $type = $this->params[0];
        $money = $this->params[1];
        $formId = $this->info['form_id'];

        if (!is_int($id)) {
            $this->send('参数 [1] 类型错误');
            return;
        }

        $types = ['+', '-', '='];
        if (!in_array($type, $types)) {
            $this->send('参数 [2] 必修是'.implode(', ', $types).'其中之一');
        }

        if (!is_numeric($money)) {
            $this->send('参数 [3] 类型错误');
            return;
        }

        if ($money < 0) {
            $this->send('参数 [3] 必须大于等于0');
            return;
        }

        $model = Wallet::query()
            ->where(WalletTrace::T_UID, $formId)
            ->where(WalletTrace::ID, $id)
            ->first();

        if (!$model) {
            $this->send('钱包不存在或不属于您');
            return;
        }

        $balanceKey = WalletTrace::BALANCE;
        $balance = $model->$balanceKey;

        if ($type === $types[0]) {
            $money = $balance + $money;
        }

        if ($type === $types[1]) {
            $money = $balance - $money;
        }

        $model = Wallet::query()
            ->where(WalletTrace::T_UID, $formId)
            ->where(WalletTrace::ID, $id)
            ->update([
                WalletTrace::BALANCE => $money,
            ]);

        $this->send($model === 1 ? '更新成功' : '更新失败');
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
