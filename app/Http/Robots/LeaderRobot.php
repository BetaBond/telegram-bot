<?php

namespace App\Http\Robots;

use App\Helpers\MessageHelper;
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
            default => false,
        };
        
        if ($message === false) {
            return false;
        }
        
        $message = MessageHelper::compatible_parsing_md2($message);
        
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
     */
    public static function join(array $params): void
    {
    
    }
    
}