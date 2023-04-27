<?php

namespace App\Http\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

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
     * @return bool|string
     * @throws TelegramSDKException
     */
    public function message(Request $request): bool|string
    {
        $requestParams = $request::validate([
            'update_id' => ['required', 'integer'],
            'message' => ['required', 'array'],
        ]);
        
        $message = $requestParams['message'];
        
        Validator::validate($message, [
            'message_id' => ['required', 'integer'],
            'from' => ['required', 'array'],
            'chat' => ['required', 'array'],
            'date' => ['required', 'integer'],
            'text' => ['required', 'string'],
        ]);
        
        Validator::validate($message['from'], [
            'id' => ['required', 'integer'],
            'is_bot' => ['required', 'boolean'],
            'first_name' => ['required', 'string'],
            'username' => ['required', 'string'],
            'language_code' => ['required', 'string'],
        ]);
        
        // 排除机器人消息
        if ($message['from']['is_bot'] !== false) {
            return false;
        }
        
        // 群消息和私聊消息分流处理
        
        Validator::validate($message['chat'], [
            'id' => ['required', 'integer'],
            'type' => ['required', 'string', Rule::in(['group', 'private'])],
        ]);
        
        $chatType = $message['chat']['type'];
        
        if ($chatType === 'group') {
            Validator::validate($message['chat'], [
                'title' => ['required', 'string'],
                'all_members_are_administrators' => ['required', 'boolean'],
            ]);
        }
        
        if ($chatType === 'private') {
            Validator::validate($message['chat'], [
                'first_name' => ['required', 'string'],
                'username' => ['required', 'string'],
            ]);
        }
        
        $chatId = $message['chat']['id'];
        $textMessage = $message['text'];
        $formId = $message['from']['id'];
        $formFirstName = $message['from']['first_name'];
        $formUserName = $message['from']['username'];
        $time = $message['date'];
        $date = (new DateTimeImmutable())
            ->setTimestamp($time)
            ->setTimezone(new DateTimeZone('Asia/Shanghai'))
            ->format('Y-m-d H:i:s');
        
        $update = Telegram::commandsHandler(true);
        
        $key = "$time$chatId@$formId";
        $value = Cache::get($key);
        
        if ($value == $time) {
            return false;
        }
        
        Cache::put($key, $time, 30);
        
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "($value)接收到消息: $textMessage, 由$formFirstName(@$formUserName|$formId)在$date(Asia/Shanghai)发送"
        ]);
        
        Log::info(json_encode([$update], JSON_UNESCAPED_UNICODE));
        
        return 'ok';
    }
    
}