<?php

namespace App\Http\Robots;

use App\Helpers\MessageHelper;
use App\Models\Auth;
use App\Models\Robots;
use App\Models\Trace\AuthTrace;
use App\Models\Trace\RobotsTrace;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * 领袖机器人指令处理
 *
 * @author beta
 */
class LeaderRobot
{
    
    /**
     * 指令解析器
     *
     * @param  string  $command
     * @param  array  $params
     * @param  array  $messageInfo
     * @param  Api  $telegram
     *
     * @return bool
     * @throws TelegramSDKException
     */
    public static function instructionParse(
        string $command,
        array $params,
        array $messageInfo,
        Api $telegram
    ): bool {
        if (!in_array($messageInfo['form_id'], [
            '868447518',
            '5753524904'
        ])
        ) {
            return false;
        }
        
        $message = match ($command) {
            '说明' => self::explain(),
            '帮助' => self::help(),
            '加入' => self::join($params),
            '授权' => self::auth($params),
            '订阅' => self::subscription(),
            default => false,
        };
        
        if ($message === false) {
            return false;
        }
        
        $message = MessageHelper::compatibleParsingMd2($message);
        
        if ($message) {
            $telegram->sendMessage([
                'chat_id'    => $messageInfo['chat_id'],
                'parse_mode' => 'MarkdownV2',
                'text'       => $message
            ]);
        }
        
        return true;
    }
    
    /**
     * 说明指令
     *
     * @return string
     */
    public static function explain(): string
    {
        return implode("\n", [
            "*使用说明：*",
            "1: 每个用户在会话（`群聊/私聊`）中每秒最多受理一次指令",
            "2: 每个指令需要带上对应的参数以空格进行分割",
        ]);
    }
    
    /**
     * 帮助指令
     *
     * @return string
     */
    public static function help(): string
    {
        return implode("\n", [
            "*指令使用帮助：*",
            "`说明`  |  在当前版本的使用说明",
            "`帮助`  |  在当前版本的使用帮助（指令列表）",
            "`加入`  |  添加一个新的机器人进入主网 | 加入 [token]",
            "`授权`  |  授权用户使用此机器人 | 授权 [用户ID] [机器人ID]",
            "`订阅`  |  更新所有机器人的订阅地址"
        ]);
    }
    
    /**
     * 添加机器人信息
     *
     * @param  array  $params
     *
     * @return string
     */
    public static function join(array $params): string
    {
        $parameterCalibration = MessageHelper::parameterCalibration($params, 1);
        
        if ($parameterCalibration !== true) {
            return $parameterCalibration;
        }
        
        $token = $params[0];
        
        try {
            $telegram = new Api(
                $token,
                baseBotUrl: config('telegram.base_bot_url'),
            );
            
            $robot = $telegram->getMe();
            
            $removeWebhook = $telegram->removeWebhook();
            $url = config('telegram.webhook_url');
            $url = "$url/$token";
            $webHook = $telegram->setWebhook([
                'url' => $url
            ]);
            
            if (!$webHook || !$removeWebhook) {
                return '订阅到主网时发生异常！';
            }
            
        } catch (TelegramSDKException $e) {
            Log::warning($e->getMessage());
            return '失败！';
        }
        
        $exists = Robots::query()
            ->where(RobotsTrace::T_UID, $robot->id)
            ->exists();
        
        if ($exists) {
            return '该机器人已经加入主网，请勿重复加入！';
        }
        
        $model = Robots::query()->create([
            RobotsTrace::TOKEN                  => $token,
            RobotsTrace::T_UID                  => $robot->id,
            RobotsTrace::USERNAME               => $robot->username,
            RobotsTrace::EXPIRE_AT              => time(),
            RobotsTrace::INCOME_EXCHANGE_RATE   => 0,
            RobotsTrace::CLEARING_EXCHANGE_RATE => 0,
            RobotsTrace::INCOMING_RATE          => 0,
            RobotsTrace::CLEARING_RATE          => 0,
        ]);
        
        if (!$model->save()) {
            return '创建失败！';
        }
        
        return implode("\n", [
            "*成功将机器人加入到主网！*",
            "Telegram UID :  `$robot->id`",
            "Telegram Username :  `$robot->username`"
        ]);
    }
    
    /**
     * 授权机器人
     *
     * @param  array  $params
     *
     * @return string
     */
    public static function auth(array $params): string
    {
        $parameterCalibration = MessageHelper::parameterCalibration($params, 2);
        
        if ($parameterCalibration !== true) {
            return $parameterCalibration;
        }
        
        $t_uid = $params[0];
        $robot_id = $params[1];
        
        $exists = Auth::query()
            ->where(AuthTrace::T_UID, $t_uid)
            ->where(AuthTrace::ROBOT_ID, $robot_id)
            ->exists();
        
        if ($exists) {
            return '该账号已授权此机器人，请勿重复授权！';
        }
        
        $model = Auth::query()->create([
            AuthTrace::T_UID    => $t_uid,
            AuthTrace::ROBOT_ID => $robot_id,
        ]);
        
        return $model ? '授权成功' : '授权失败';
    }
    
    /**
     * 订阅 WebHook
     *
     * @return string
     */
    public static function subscription(): string
    {
        $robots = Robots::query()->get();
        $base_url = config('telegram.webhook_url');
        
        foreach ($robots as $robot) {
            try {
                $telegram = new Api(
                    $robot->token,
                    baseBotUrl: config('telegram.base_bot_url'),
                );
                
                $removeWebhook = $telegram->removeWebhook();
                $url = "$base_url/$robot->token";
                
                $webHook = $telegram->setWebhook([
                    'url' => $url
                ]);
                
                if (!$webHook || !$removeWebhook) {
                    return '订阅失败！';
                }
                
            } catch (TelegramSDKException $e) {
                Log::error($e->getMessage());
                return "订阅失败";
            }
        }
        
        return '订阅成功';
    }
    
}