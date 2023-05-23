<?php

namespace App\Http\Robots;

use App\Helpers\MessageHelper;
use App\Models\Robots;
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
     * @return bool
     * @throws TelegramSDKException
     */
    public static function instructionParse(
        string $command,
        array $params,
        array $messageInfo,
        Api $telegram
    ): bool {
        $message = match ($command) {
            '说明' => self::explain(),
            '帮助' => self::help(),
            '加入' => self::join($params),
            '授权' => self::auth($params, $telegram),
            default => false,
        };
        
        if ($message === false) {
            return false;
        }
        
        $message = MessageHelper::compatibleParsingMd2($message);
        
        if ($message) {
            $telegram->sendMessage([
                'chat_id' => $messageInfo['chat_id'],
                'parse_mode' => 'MarkdownV2',
                'text' => $message
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
            "`授权`  |  授权用户使用此机器人 | 授权 [username]"
        ]);
    }
    
    /**
     * 添加机器人信息
     *
     * @param  array  $params
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
            $url = "https://robot.southwan.cn/api/telegram/webhook/message/$token";
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
            RobotsTrace::TOKEN => $token,
            RobotsTrace::T_UID => $robot->id,
            RobotsTrace::USERNAME => $robot->username,
            RobotsTrace::EXPIRE_AT => time(),
            RobotsTrace::INCOMING_RATE => 0,
            RobotsTrace::PAYMENT_EXCHANGE_RATE => 0,
            RobotsTrace::RATING => 0,
        ]);
        
        if (!$model->save()) {
            return '创建失败！';
        }
        
        return implode("\n", [
            "*成功将机器人加入到主网！*",
            "`Telegram UID` :  $robot->id",
            "`Telegram Username` :  @$robot->username"
        ]);
    }
    
    /**
     * @throws TelegramSDKException
     */
    public static function auth(array $params, Api $telegram): bool|string
    {
        $chatMember = $telegram->getChat([
            'chat_id' => $params[0]
        ]);
        
        return json_encode([$chatMember]);
    }
    
}