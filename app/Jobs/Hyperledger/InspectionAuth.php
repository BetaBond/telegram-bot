<?php

namespace App\Jobs\Hyperledger;

use App\Helpers\MessageHelper;
use App\Models\Auth;
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
 * 检查授权命令
 *
 * @author beta
 */
class InspectionAuth implements ShouldQueue
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
    public function handle(): void
    {
        $formId = $this->info['form_id'];

        $messages = ["*您所拥有的机器人授权信息：*"];

        $model = Auth::query()
            ->where(AuthTrace::T_UID, $formId);

        $model = $model->with([
            'robot' => function ($query) {
                $query->select([
                    RobotsTrace::ID,
                    RobotsTrace::USERNAME,
                ]);
            }
        ])->get();

        foreach ($model as $item) {
            $robot = $item->robot();
            $msg = '[ID: '.$robot->id.']';
            $messages[] = $msg;
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
        Log::error(__CLASS__.'('.__LINE__.')'.': '.$e->getMessage());
    }

}
