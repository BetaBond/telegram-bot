<?php

namespace App\Http\Controllers;

use App\Helpers\MessageHelper;
use App\Http\Service\WebhookService;
use Illuminate\Support\Facades\Cache;
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
    public function messages(Request $request): bool|string
    {
        $requestParams = $request::validate([
            'update_id' => ['required', 'integer'],
            'message' => ['required', 'array'],
        ]);
        
        $messages = $requestParams['message'];
        
        return WebhookService::messages($messages, $this->telegram);
        
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
        $formUserName = $message['from']['username'];
        $time = $message['date'];
        
        Telegram::commandsHandler(true);
        
        $key = "$time$chatId@$formId";
        $value = Cache::get($key);
        
        if ($value == $time) {
            return false;
        }
        
        Cache::put($key, $time, 30);
        
        if (!in_array($formId, [868447518, 5753524904])) {
            return false;
        }
        
        // 指令解析器
        $textMessage = explode(' ', $textMessage);
        
        if (empty($textMessage)) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => '指令错误'
            ]);
        }
        
        $command = $textMessage[0];
        array_shift($textMessage);
        
        $message = match ($command) {
            '说明' => WebhookService::explain(),
            '帮助' => WebhookService::help(),
            '汇率' => WebhookService::rate($textMessage),
            '费率' => WebhookService::rating($textMessage),
            '进账', '+' => WebhookService::income($textMessage, $formUserName, $formId),
            '出账', '-' => WebhookService::clearing($textMessage, $formUserName, $formId),
            '重置' => WebhookService::reset(),
            '数据' => WebhookService::dataMessage(),
            default => false,
        };
        
        $message = MessageHelper::compatible_parsing_md2($message);
        
        if ($message) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'parse_mode' => 'MarkdownV2',
                'text' => $message
            ]);
        }
        
        return 'ok';
    }
    
}