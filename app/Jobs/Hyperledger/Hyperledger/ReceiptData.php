<?php

namespace App\Jobs\Hyperledger\Hyperledger;

use App\Helpers\MessageHelper;
use App\Models\Book;
use App\Models\Hyperledger;
use App\Models\Robots;
use App\Models\Trace\BookTrace;
use App\Models\Trace\HyperledgerTrace;
use App\Models\Trace\RobotsTrace;
use App\Models\Trace\WalletTrace;
use App\Models\Wallet;
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
 * 进账数据任务
 *
 * @author beta
 */
class ReceiptData implements ShouldQueue
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
        private array $data
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
     * 总笔数计算
     *
     * @return int
     */
    private function totalStrokes(): int
    {
        $totalStrokes = 0;

        foreach ($this->data as $datum) {
            $totalStrokes += count($datum);
        }

        return $totalStrokes;
    }

    /**
     * 执行这个任务
     *
     * @return void
     */
    public function handle(): void
    {
        $messages = [];

        $totalStrokes = $this->totalStrokes();

        $messages[] = "今日入款 ($totalStrokes 笔) :";
        $messages[] = '';

        $moneyKey = HyperledgerTrace::MONEY;
        $remarkKey = HyperledgerTrace::REMARK;
        $rateKey = HyperledgerTrace::RATE;
        $exchangeRateKey = HyperledgerTrace::EXCHANGE_RATE;
        $createdAtKey = HyperledgerTrace::CREATED_AT;

        $totalAmount = (object) ['rmb' => 0, 'usdt' => 0];

        foreach ($this->data as $username => $datum) {
            $messages[] = '来自 @'.$username.' ('.count($datum).' 笔) :';
            $messages[] = '';

            foreach ($datum as $uuid => $item) {
                $date = date('H:i:s', $item->$createdAtKey);
                $msg = '[`'.$uuid.'`] [`'.$date.'`]';

                if (!empty($item->$remarkKey)) {
                    $msg .= ' [`'.$item->$remarkKey.'`]';
                }

                $messages[] = $msg;

                $item->$moneyKey = $item->$moneyKey * 1;
                $item->$rateKey = $item->$rateKey * 1;
                $item->$exchangeRateKey = $item->$exchangeRateKey * 1;

                $resultMoney = 0;

                if (
                    !empty($item->$exchangeRateKey)
                    && !empty($item->$rateKey)
                ) {
                    $resultMoney = $item->$moneyKey * $item->$rateKey;
                    $resultMoney = $resultMoney / $item->$exchangeRateKey;
                    $resultMoney = round($resultMoney, 2);
                }

                $msg = '[`('.$item->$moneyKey.' * '.$item->$rateKey.') ';
                $msg .= '/ '.$item->$exchangeRateKey.' = '.$resultMoney.'`]';

                $messages[] = $msg;
                $messages[] = '';

                $totalAmount->rmb += $item->$moneyKey;
                $totalAmount->usdt += $resultMoney;
            }
        }

        $msg = '合计入款 : [`￥';
        $msg .= $totalAmount->rmb.'` / `₮';
        $msg .= $totalAmount->usdt.'`]';
        $messages[] = $msg;

        // 分批发送
        if ($totalStrokes > 50) {
            $this->send('数据量过于庞大无法发送!');
            return;
        }

        $this->send(implode("\n", $messages));
    }

    /**
     * 处理失败处理
     *
     * @param  Throwable  $e
     */
    public function failed(Throwable $e): void
    {
        Log::error(__CLASS__.'('.__LINE__.')'.': ('.$e->getLine().')'
            .$e->getMessage());
    }

}
