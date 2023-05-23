<?php

namespace App\Http\Service;

use App\Models\Bill;
use App\Models\Trace\BillTrace;
use Illuminate\Support\Facades\Cache;
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
     * @return bool|string
     * @throws TelegramSDKException
     */
    public static function messages(array $message, Api $telegram): bool|string
    {
        $message = Validator::validate($message, [
            'message_id' => ['required', 'integer'],
            // 来源信息
            'from' => ['required', 'array'],
            // 聊天信息
            'chat' => ['required', 'array'],
            'date' => ['required', 'integer'],
            'text' => ['required', 'string'],
        ]);
        
        $message['from'] = self::form($message['from']);
        
        // 排除机器人消息
        if ($message['from']['is_bot'] !== false) {
            return false;
        }
    
        $message['chat'] = self::chat($message['chat']);
        $chatType = $message['chat']['type'];
        
        $message['chat'] = match ($chatType) {
            'privateChat' => self::privateChat($message['chat']),
            default => self::groupChat($message['chat']),
        };
        
        // 整理需要的信息
        $messageInfo = [
            'chat_id' => $message['chat']['id'],
            'form_id' => $message['from']['id'],
            'form_user_name' => $message['from']['username'],
            'text_message' => $message['text'],
            'timestamp' => $message['date'],
        ];
        
        $preventRepetition = self::preventRepetition(
            $messageInfo['timestamp'],
            $messageInfo['chat_id'],
            $messageInfo['form_id'],
        );
        
        if (!$preventRepetition) {
            return false;
        }
        
        return self::instructionParse($messageInfo, $telegram);
    }
    
    /**
     * 指令解析器
     *
     * @throws TelegramSDKException
     */
    public static function instructionParse(array $messageInfo, Api $telegram)
    {
        $textMessage = explode(' ', $messageInfo['text_message']);
        
        if (empty($textMessage)) {
            $telegram->sendMessage([
                'chat_id' => $messageInfo['chat_id'],
                'text' => '指令错误'
            ]);
            
            return false;
        }
        
        $command = $textMessage[0];
        $params = $textMessage;
        
        array_shift($params);
        
        return true;
    }
    
    
    /**
     * 防止消息重复受理策略 (同一会话)
     *
     * @param  string  $time
     * @param  int  $chatId
     * @param  int  $formId
     * @return bool
     */
    public static function preventRepetition(
        string $time,
        int $chatId,
        int $formId
    ): bool {
        $key = "$time$chatId@$formId";
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
     * @return array
     */
    public static function form(array $form): array
    {
        return Validator::validate($form, [
            'id' => ['required', 'integer'],
            'is_bot' => ['required', 'boolean'],
            'first_name' => ['required', 'string'],
            'username' => ['required', 'string'],
        ]);
    }
    
    /**
     * 验证聊天信息完整性
     *
     * @param  array  $chat
     * @return array
     */
    public static function chat(array $chat): array
    {
        return Validator::validate($chat, [
            'id' => ['required', 'integer'],
            'type' => ['required', 'string', Rule::in(['group', 'private'])],
        ]);
    }
    
    /**
     * 验证私聊消息完整性
     *
     * @param  array  $chat
     * @return array
     */
    public static function privateChat(array $chat): array
    {
        return Validator::validate($chat, [
            'first_name' => ['required', 'string'],
            'username' => ['required', 'string'],
        ]);
    }
    
    /**
     * 验证群聊信息完整性
     *
     * @param  array  $chat
     * @return array
     */
    public static function groupChat(array $chat): array
    {
        return Validator::validate($chat, [
            'title' => ['required', 'string'],
            'all_members_are_administrators' => ['required', 'boolean'],
        ]);
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
            "3: 所有指令消息都会被保护，所以无法转发或复制"
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
            "`汇率`  |  设置当前的汇率 | 汇率 [出账/进账/费率] [小数]",
            "`费率`  |  设置当前的费率 | 费率 [小数]",
            "`进账`  |  设置当前进账金额 | 进账 [小数]",
            "`出账`  |  设置当前出账金额 | 出账 [小数]",
            "`+`\t\t\t\t\t\t\t\t|  进账的别名用法",
            "`-`\t\t\t\t\t\t\t\t|  出账的别名用法",
            "`重置`  |  重置进出账数据",
            "`数据`  |  获取当日所有进出账数据",
        ]);
    }
    
    /**
     * 设置汇率信息
     *
     * @param  array  $params
     * @return string
     */
    public static function rate(array $params): string
    {
        if (empty($params)) {
            return "参数错误";
        }
        
        if (count($params) !== 2) {
            return "参数不足";
        }
        
        $type = [
            '进账' => 'income_exchange_rate',
            '出账' => 'clearing_exchange_rate',
            '费率' => 'rate_exchange_rate',
        ];
        
        if (!in_array($params[0], array_keys($type))) {
            return "第一个参数必须是[进账 | 出账 | 费率]其中之一";
        }
        
        if (!is_numeric($params[1])) {
            return "参数类型错误";
        }
        
        $exchangeRate = (float) $params[1];
        
        if ($exchangeRate <= 0) {
            return "汇率必须大于0";
        }
        
        $cache = Cache::put($type[$params[0]], $exchangeRate);
        
        if ($cache) {
            $exchangeRate = Cache::get($type[$params[0]]);
            return implode("\n", [
                "*设置成功！！！*",
                "当前为：`$exchangeRate`"
            ]);
        }
        
        return "失败";
    }
    
    /**
     * 设置费率
     *
     * @param  array  $params
     * @return string
     */
    public static function rating(array $params): string
    {
        if (empty($params)) {
            return "参数错误";
        }
        
        if (!is_numeric($params[0])) {
            return "参数类型错误";
        }
        
        $exchangeRate = (float) $params[0];
        
        $cache = Cache::put('rate_exchange_rate', $exchangeRate);
        
        if ($cache) {
            $exchangeRate = Cache::get('rate_exchange_rate');
            return implode("\n", [
                "*设置成功！！！*",
                "当前为：`$exchangeRate`"
            ]);
        }
        
        return "失败";
    }
    
    /**
     * 设置进账信息
     *
     * @param  array  $params
     * @param  string  $formUserName
     * @param  int  $tUID
     * @return string
     */
    public static function income(
        array $params,
        string $formUserName,
        int $tUID
    ): string {
        $billValidate = self::billValidate($params);
        if ($billValidate !== true) {
            return $billValidate;
        }
        
        $money = (float) $params[0];
        $exchangeRate = Cache::get('income_exchange_rate', false);
        
        $model = Bill::query()->create([
            BillTrace::MONEY => $money,
            BillTrace::EXCHANGE_RATE => (float) $exchangeRate,
            BillTrace::TYPE => 1,
            BillTrace::T_UID => $tUID,
            BillTrace::USERNAME => $formUserName,
        ])->save();
        
        if ($model) {
            return self::dataMessage();
        }
        
        return "失败";
    }
    
    /**
     * 设置出账信息
     *
     * @param  array  $params
     * @param  string  $formUserName
     * @param  int  $tUID
     * @return string
     */
    public static function clearing(
        array $params,
        string $formUserName,
        int $tUID
    ): string {
        $billValidate = self::billValidate($params);
        if ($billValidate !== true) {
            return $billValidate;
        }
        
        $money = (float) $params[0];
        $exchangeRate = Cache::get('clearing_exchange_rate', false);
        
        $model = Bill::query()->create([
            BillTrace::MONEY => $money,
            BillTrace::EXCHANGE_RATE => (float) $exchangeRate,
            BillTrace::TYPE => -1,
            BillTrace::T_UID => $tUID,
            BillTrace::USERNAME => $formUserName,
        ])->save();
        
        if ($model) {
            return self::dataMessage();
        }
        
        return "失败";
    }
    
    /**
     * 重置指令
     *
     * @return string
     */
    public static function reset(): string
    {
        Bill::query()->delete();
        
        return "重置成功！";
    }
    
    /**
     * 数据消息
     *
     * @return string
     */
    public static function dataMessage(): string
    {
        // 进账数据
        $income = Bill::query()->whereBetween(BillTrace::CREATED_AT, [
            strtotime(date('Y-m-d').'00:00:00'),
            strtotime(date('Y-m-d').'23:59:59'),
        ])->where('type', 1)->get()->toArray();
        
        // 出账数据
        $clearing = Bill::query()->whereBetween(BillTrace::CREATED_AT, [
            strtotime(date('Y-m-d').'00:00:00'),
            strtotime(date('Y-m-d').'23:59:59'),
        ])->where('type', -1)->get()->toArray();
        
        $messages = [];
        $formMessage = [];
        
        $formMessage = self::build($formMessage, $income, 'income');
        $formMessage = self::build($formMessage, $clearing, 'clearing');
        
        $messages[] = '进账（'.count($income).' 笔）：';
        $messages[] = '';
        
        $bill = 0;
        $incomeMoney = 0;
        
        // 构建进账字符信息
        foreach ($formMessage as $items) {
            if (isset($items['income']) && !empty($items['income']['messages'])) {
                $messages[] = '来自 @'.$items['username'].'（'.count($items['income']['messages']).' 笔）：';
                foreach ($items['income']['messages'] as $item) {
                    $messages[] = $item;
                }
                $messages[] = '';
                $bill += $items['income']['difference'];
                $incomeMoney += $items['income']['money'];
            }
        }
        
        $messages[] = "合计进账：[`￥$incomeMoney`]";
        $messages[] = '';
        
        $messages[] = '出账（'.count($clearing).' 笔）：';
        $messages[] = '';
        
        // 构建出账信息
        foreach ($formMessage as $items) {
            if (isset($items['clearing']) && !empty($items['clearing']['messages'])) {
                $messages[] = '来自 @'.$items['username'].'（'.count($items['clearing']['messages']).' 笔）：';
                foreach ($items['clearing']['messages'] as $item) {
                    $messages[] = $item;
                }
                $messages[] = '';
                $bill -= $items['clearing']['difference'];
            }
        }
        
        $messages[] = '合计差额：	[ `₮'.round((float) $bill, 2).'` ]';
        
        return implode("\n", $messages);
    }
    
    /**
     * 构建数据字符串
     *
     * @param  array  $formMessage
     * @param  array  $data
     * @param  string  $key
     * @return array
     */
    public static function build(array $formMessage, array $data, string $key): array
    {
        foreach ($data as $item) {
            if (!isset($formMessage[$item[BillTrace::T_UID]][$key]['difference'])) {
                $formMessage[$item[BillTrace::T_UID]][$key]['difference'] = 0;
            }
            
            if (!isset($formMessage[$item[BillTrace::T_UID]][$key]['money'])) {
                $formMessage[$item[BillTrace::T_UID]][$key]['money'] = 0;
            }
            
            $username = $item[BillTrace::USERNAME];
            $date = date('H:i:s', (int) $item[BillTrace::CREATED_AT]);
            $money = (float) $item[BillTrace::MONEY];
            $exchangeRate = (float) $item[BillTrace::EXCHANGE_RATE];
            
            $rateExchangeRate = Cache::get('rate_exchange_rate', false);
            $difference = 0;
            
            // 数学计算
            if (!empty($money) && !empty($exchangeRate)) {
                if ($key === 'income') {
                    $difference = $money * $rateExchangeRate / $exchangeRate;
                }
                
                if ($key === 'clearing') {
                    $difference = $money / ($exchangeRate - 0.045);
                }
            }
            
            $difference = round($difference, 2);
            $formMessage[$item[BillTrace::T_UID]][$key]['difference'] += $difference;
            $formMessage[$item[BillTrace::T_UID]][$key]['money'] += $money;
            
            // 构建字符串
            $messageString = "[`$date`]  ";
            
            if ($key === 'income') {
                $messageString .= "$money\*$rateExchangeRate/$exchangeRate=$difference";
            }
            
            if ($key === 'clearing') {
                $messageString .= "$money/($exchangeRate-0.045)=$difference";
            }
            
            $formMessage[$item[BillTrace::T_UID]]['username'] = $username;
            $formMessage[$item[BillTrace::T_UID]][$key]['messages'][] = $messageString;
        }
        
        return $formMessage;
    }
    
    /**
     * 账单参数验证
     *
     * @param  array  $params
     * @return bool|string
     */
    public static function billValidate(array $params): bool|string
    {
        if (empty($params)) {
            return "参数错误";
        }
        
        if (!is_numeric($params[0])) {
            return "参数类型错误";
        }
        
        $money = (float) $params[0];
        
        if ($money <= 0) {
            return "金额必须大于0";
        }
        
        $type = [
            '进账汇率' => 'income_exchange_rate',
            '出账汇率' => 'clearing_exchange_rate',
            '费率' => 'rate_exchange_rate',
        ];
        
        foreach ($type as $key => $value) {
            $exchangeRate = Cache::get($value, false);
            if ($exchangeRate === false) {
                return "[$key] 未设置";
            }
        }
        
        return true;
    }
    
}