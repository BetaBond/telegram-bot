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
 * 我的钱包命令
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
    public
    function handle(): void
    {
        $formId = $this->info['form_id'];

        $messages = ["*您所拥有的所有钱包信息: *"];
        $messages[] = '';

        $model = Wallet::query()
            ->where(WalletTrace::T_UID, $formId)
            ->get();

        foreach ($model as $item) {
            $nameKey = WalletTrace::NAME;
            $balanceKey = WalletTrace::BALANCE;

            $messages[] = '*'.$item->$nameKey.':*';
            $messages[] = '余额: '.$item->$balanceKey;
            $messages[] = '';
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
