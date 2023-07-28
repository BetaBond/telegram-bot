<?php

namespace App\Http\Controllers;

use App\Http\Service\WebhookService;
use App\Jobs\Leader\LeaderDistributeJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * 消息控制器
 *
 * @author southwan
 */
class WebhookController
{
    
    /**
     * 实例类
     *
     * @var Api
     */
    protected Api $telegram;
    
    /**
     * 创建一个新的控制器实例
     *
     * @param  Api  $telegram
     */
    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
    }
    
    /**
     * 消息处理中心
     *
     * @param  Request  $request
     * @param  string  $token
     *
     * @return bool|string
     * @throws TelegramSDKException
     */
    public function message(Request $request, string $token): bool|string
    {
        $requestParams = $request::validate([
            'update_id' => ['required', 'integer'],
            'message'   => ['required', 'array'],
        ]);
        
        $messages = $requestParams['message'];
        
        $telegram = new Api(
            $token,
            baseBotUrl: config('telegram.base_bot_url'),
        );
        
        Log::info('接收: '.$telegram->getMe()->id);
        
        return WebhookService::messages($messages, $telegram);
    }
    
    /**
     * Job 模式的消息处理中心
     *
     * @param  Request  $request
     * @param  string  $token
     *
     * @return bool
     */
    public function job(Request $request, string $token): bool
    {
        $requestParams = $request::validate([
            'update_id' => ['required', 'integer'],
            'message'   => ['required', 'array'],
        ]);
        
        // 详细验证
        $message = Validator::validate($requestParams['message'], [
            'message_id' => ['required', 'integer'],
            // 来源信息
            'from'       => ['required', 'array'],
            // 聊天信息
            'chat'       => ['required', 'array'],
            'date'       => ['required', 'integer'],
            'text'       => ['required', 'string'],
        ]);
        
        $message['from'] = WebhookService::form($message['from']);
        
        // 排除机器人消息
        if ($message['from']['is_bot'] !== false) {
            return false;
        }
        
        $chat = WebhookService::chat($message['chat']);
        $chatType = $message['chat']['type'];
        
        $message['chat'] = array_merge($chat, match ($chatType) {
            'private' => WebhookService::privateChat($message['chat']),
            default => WebhookService::groupChat($message['chat']),
        });
        
        // 整理需要的信息
        $messageInfo = [
            'chat_id'        => $message['chat']['id'],
            'form_id'        => $message['from']['id'],
            'form_user_name' => $message['from']['username'],
            'text_message'   => $message['text'],
            'timestamp'      => $message['date'],
        ];
        
        $preventRepetition = WebhookService::preventRepetition(
            $messageInfo['timestamp'],
            $messageInfo['chat_id'],
            $messageInfo['form_id']
        );
        
        if (!$preventRepetition) {
            return false;
        }
        
        $textMessage = explode(' ', $messageInfo['text_message']);
        
        $command = $textMessage[0];
        $params = $textMessage;
        
        array_shift($params);
        
        Log::info(json_encode($messageInfo));
        if ($messageInfo['form_id'] == 5669756920) {
            LeaderDistributeJob::dispatch(
                $token,
                $messageInfo,
                $command,
                $params,
            );
        }
        
        return true;
    }
    
}