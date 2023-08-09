<?php

namespace App\Jobs\Hyperledger\Hyperledger;

use App\Helpers\MessageHelper;
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
 * 超级账本入款命令
 *
 * @author beta
 */
class Receipt implements ShouldQueue
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
            1
        );

        if ($parameterCalibration !== true) {
            $this->send($parameterCalibration);
            return;
        }

        $money = $this->params[0];
        $remark = $this->params[1] ?? '';

        $formId = $this->info['form_id'];
        $formUserName = $this->info['form_user_name'];

        if (!is_numeric($money)) {
            $this->send('参数 [1] 类型错误');
            return;
        }

        if ($money < 0) {
            $this->send('参数 [1] 必须大于等于0');
            return;
        }

        try {
            $telegram = new Api(
                $this->token,
                baseBotUrl: config('telegram.base_bot_url'),
            );

            $robot = $telegram->getMe();
        } catch (TelegramSDKException $e) {
            Log::error(__CLASS__.'('.__LINE__.')'.': '.$e->getMessage());
            $this->send('内部错误');
            return;
        }

        $model = Robots::query()
            ->where(RobotsTrace::T_UID, $robot->id)
            ->first();

        $rateKey = RobotsTrace::INCOMING_RATE;
        $exchangeRateKey = RobotsTrace::INCOME_EXCHANGE_RATE;

        $rate = $model->$rateKey;
        $exchangeRate = $model->$exchangeRateKey;

        $model = Hyperledger::query()->create([
            HyperledgerTrace::TYPE          => 1,
            HyperledgerTrace::T_UID         => $formId,
            HyperledgerTrace::ROBOT_ID      => $robot->id,
            HyperledgerTrace::EXCHANGE_RATE => (float) $exchangeRate,
            HyperledgerTrace::RATE          => (float) $rate,
            HyperledgerTrace::MONEY         => $money,
            HyperledgerTrace::USERNAME      => $formUserName,
            HyperledgerTrace::REMARK        => $remark,
            HyperledgerTrace::WALLET_ID     => 0,
        ]);

        $this->send($model->save() ? '进账成功' : '进账失败');
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
