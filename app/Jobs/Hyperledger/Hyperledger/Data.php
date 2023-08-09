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
 * 超级账本数据命令
 *
 * @author beta
 */
class Data implements ShouldQueue
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

        // 查询所有数据
        $hyperledger = Hyperledger::query()
            ->whereBetween(BookTrace::CREATED_AT, [
                strtotime(date('Y-m-d').'00:00:00'),
                strtotime(date('Y-m-d').'23:59:59'),
            ])->where(
                BookTrace::ROBOT_ID,
                $robot->id
            );

        // 条件筛选
        if (count($this->params) === 1) {
            $username = $this->params[0];
            $username = str_replace('@', '', $username);

            $hyperledger = $hyperledger->where(
                HyperledgerTrace::USERNAME,
                $username
            );
        }

        // 取出数据
        $hyperledger = $hyperledger->get()->toArray();

        $this->send(json_encode($hyperledger));
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
