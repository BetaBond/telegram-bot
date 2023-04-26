<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        
        Log::info('1');
        
        Validator::validate($message['from'], [
            'id' => ['required', 'integer'],
            'is_bot' => ['required', 'boolean'],
            'first_name' => ['required', 'string'],
            'username' => ['required', 'string'],
            'language_code' => ['required', 'string'],
        ]);
        
        Log::info('2');
        
        // 排除机器人消息
        if ($message['from']['is_bot'] !== false) {
            return false;
        }
        
        Log::info('3');
        
        // 群消息和私聊消息分流处理
        
        Validator::validate($message['chat'], [
            'id' => ['required', 'integer'],
            'type' => ['required', 'string', Rule::in(['group', 'private'])],
        ]);
        
        Log::info('4');
        
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
        
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "接收到消息: $textMessage, 由$formFirstName(@$formUserName|$formId)发送"
        ]);
    
        return 'ok';
    }
    
}