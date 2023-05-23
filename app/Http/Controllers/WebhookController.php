<?php

namespace App\Http\Controllers;

use App\Http\Service\WebhookService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
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
     * @return bool|string
     * @throws TelegramSDKException
     */
    public function message(Request $request, string $token): bool|string
    {
        $requestParams = $request::validate([
            'update_id' => ['required', 'integer'],
            'message' => ['required', 'array'],
        ]);
        
        $messages = $requestParams['message'];
        
        $telegram = new Api(
            $token,
            baseBotUrl: config('telegram.base_bot_url'),
        );
        
        return WebhookService::messages($messages, $telegram);
    }
    
}