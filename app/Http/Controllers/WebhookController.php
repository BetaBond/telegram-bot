<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
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
     * @return bool
     * @throws TelegramSDKException
     */
    public function message(Request $request): bool
    {
        $requestParams = $request::validate([
            'update_id' => ['required', 'integer'],
            'message' => ['required', 'array'],
        ]);
        
        $request::validate([
            'message_id' => ['required', 'integer'],
            'from' => ['required', 'array'],
            'chat' => ['required', 'array'],
            'date' => ['required', 'integer'],
            'text' => ['required', 'string'],
        ], $requestParams['message']);
        
        $request::validate([
            'id' => ['required', 'integer'],
            'is_bot' => ['required', 'boolean'],
            'first_name' => ['required', 'string'],
            'username' => ['required', 'string'],
            'language_code' => ['required', 'string'],
        ], $requestParams['message']['from']);
        
        // 排除机器人消息
        if ($requestParams['message']['from']['is_bot'] !== false) {
            return false;
        }
        
        // 群消息和私聊消息分流处理
        
        $request::validate([
            'id' => ['required', 'integer'],
            'type' => ['required', 'string', Rule::in(['group', 'private'])],
        ], $requestParams['message']['chat']);
        
        $chatType = $requestParams['message']['chat']['type'];
        
        if ($chatType === 'group') {
            $request::validate([
                'title' => ['required', 'string'],
                'all_members_are_administrators' => ['required', 'boolean'],
            ], $requestParams['message']['chat']);
        }
        
        if ($chatType === 'private') {
            $request::validate([
                'first_name' => ['required', 'string'],
                'username' => ['required', 'string'],
            ], $requestParams['message']['chat']);
        }
        
        $chatId = $requestParams['message']['chat']['id'];
        $textMessage = $requestParams['message']['text'];
        $formId = $requestParams['form']['id'];
        $formFirstName = $requestParams['form']['first_name'];
        $formUserName = $requestParams['form']['user_name'];
        
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "接收到消息: $textMessage, 由$formFirstName(@$formUserName|$formId)发送 "
        ]);
        
        return true;
    }
    
}