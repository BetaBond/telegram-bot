<?php

namespace App\Http\Service;

use App\Http\Robots\BaseBillRobot;
use App\Jobs\LeaderDistributeJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * Webhook 服务类
 *
 * @author southwan
 */
class WebhookService
{
    
    /**
     * 消息处理
     *
     * @param  array  $message
     * @param  Api  $telegram
     *
     * @return bool
     * @throws TelegramSDKException
     */
    public static function messages(array $message, Api $telegram): bool
    {
        $message = Validator::validate($message, [
            'message_id' => ['required', 'integer'],
            // 来源信息
            'from'       => ['required', 'array'],
            // 聊天信息
            'chat'       => ['required', 'array'],
            'date'       => ['required', 'integer'],
            'text'       => ['required', 'string'],
        ]);
        
        $message['from'] = self::form($message['from']);
        
        // 排除机器人消息
        if ($message['from']['is_bot'] !== false) {
            return false;
        }
        
        $chat = self::chat($message['chat']);
        $chatType = $message['chat']['type'];
        
        $message['chat'] = array_merge($chat, match ($chatType) {
            'private' => self::privateChat($message['chat']),
            default => self::groupChat($message['chat']),
        });
        
        // 整理需要的信息
        $messageInfo = [
            'chat_id'        => $message['chat']['id'],
            'form_id'        => $message['from']['id'],
            'form_user_name' => $message['from']['username'],
            'text_message'   => $message['text'],
            'timestamp'      => $message['date'],
        ];
        
        $robot = $telegram->getMe();
        
        $preventRepetition = self::preventRepetition(
            $messageInfo['timestamp'],
            $messageInfo['chat_id'],
            $messageInfo['form_id'],
            $robot->id,
        );
        
        if (!$preventRepetition) {
            return false;
        }
        
        return self::instructionParse($messageInfo, $telegram);
    }
    
    /**
     * 指令解析器
     *
     * @param  array  $messageInfo
     * @param  Api  $telegram
     *
     * @return bool
     * @throws TelegramSDKException
     */
    public static function instructionParse(
        array $messageInfo,
        Api $telegram
    ): bool {
        $textMessage = explode(' ', $messageInfo['text_message']);
        
        if (empty($textMessage)) {
            $telegram->sendMessage([
                'chat_id' => $messageInfo['chat_id'],
                'text'    => '指令错误'
            ]);
            
            return false;
        }
        
        $command = $textMessage[0];
        $params = $textMessage;
        
        array_shift($params);
        
        Log::info('分发: ');
        
        $robot = $telegram->getMe();
        
        Log::info('分发: '.$robot->id);
        
        // 分发给对应职能的机器人
        match ($robot->username) {
            'jungle_leader_bot' => LeaderDistributeJob::dispatch(
                $robot->id,
                $messageInfo,
                $command,
                $params
            ),
            default => BaseBillRobot::instructionParse($command, $params,
                $messageInfo, $telegram),
        };
        
        return true;
    }
    
    
    /**
     * 防止消息重复受理策略 (同一会话)
     *
     * @param  string  $time
     * @param  int  $chatId
     * @param  int  $formId
     * @param  int  $robotId
     *
     * @return bool
     */
    public static function preventRepetition(
        string $time,
        int $chatId,
        int $formId,
        int $robotId = 0
    ): bool {
        $key = md5("$time|$chatId|$formId|$robotId");
        $value = Cache::get($key);
        
        if ($value == $time) {
            return false;
        }
        
        Cache::put($key, $time, 30);
        
        return true;
    }
    
    /**
     * 消息来源信息
     *
     * @param  array  $form
     *
     * @return array
     */
    public static function form(array $form): array
    {
        return Validator::validate($form, [
            'id'         => ['required', 'integer'],
            'is_bot'     => ['required', 'boolean'],
            'first_name' => ['required', 'string'],
            'username'   => ['required', 'string'],
        ]);
    }
    
    /**
     * 验证聊天信息完整性
     *
     * @param  array  $chat
     *
     * @return array
     */
    public static function chat(array $chat): array
    {
        return Validator::validate($chat, [
            'id'   => ['required', 'integer'],
            'type' => ['required', 'string', Rule::in(['group', 'private'])],
        ]);
    }
    
    /**
     * 验证私聊消息完整性
     *
     * @param  array  $chat
     *
     * @return array
     */
    public static function privateChat(array $chat): array
    {
        return Validator::validate($chat, [
            'first_name' => ['required', 'string'],
            'username'   => ['required', 'string'],
        ]);
    }
    
    /**
     * 验证群聊信息完整性
     *
     * @param  array  $chat
     *
     * @return array
     */
    public static function groupChat(array $chat): array
    {
        return Validator::validate($chat, [
            'title'                          => ['required', 'string'],
            'all_members_are_administrators' => ['required', 'boolean'],
        ]);
    }
    
}