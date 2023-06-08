<?php

namespace App\Http\Robots;

use App\Exports\BaseBillExport;
use App\Helpers\MessageHelper;
use App\Models\Auth;
use App\Models\Bill;
use App\Models\Robots;
use App\Models\Trace\AuthTrace;
use App\Models\Trace\BillTrace;
use App\Models\Trace\RobotsTrace;
use Maatwebsite\Excel\Facades\Excel;
use \Maatwebsite\Excel\Excel as ExcelType;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * 基础型账本机器人
 *
 * @author southwan
 */
class BaseBillRobot
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
            '我的' => self::mine($messageInfo['form_id'], $messageInfo['form_user_name']),
            default => false,
        };
        
        if ($message === false) {
            $robot = $telegram->getMe();
            
            $exists = Auth::query()
                ->where(AuthTrace::ROBOT_ID, $robot->id)
                ->where(AuthTrace::T_UID, $messageInfo['form_id'])
                ->exists();
            
            if ($exists) {
                $message = match ($command) {
                    '说明' => self::explain(),
                    '帮助' => self::help(),
                    '汇率' => self::rate($params, $robot->id),
                    '费率' => self::rating($params, $robot->id),
                    '进账', '+' => self::income(
                        $params,
                        $messageInfo['form_user_name'],
                        $messageInfo['form_id'],
                        $robot->id
                    ),
                    '出账', '-' => self::clearing(
                        $params,
                        $messageInfo['form_user_name'],
                        $messageInfo['form_id'],
                        $robot->id
                    ),
                    '重置' => self::reset($params, $robot->id),
                    '数据' => self::dataMessage($params, $robot->id),
                    '导出' => self::export($robot->id),
                    default => false,
                };
            }
            
            if ($message === false) {
                return false;
            }
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
     * 获取关于我的所有信息
     *
     * @param  int  $uid
     * @param  string  $username
     * @return string
     */
    public static function mine(int $uid, string $username): string
    {
        return implode("\n", [
            "*您的信息：*",
            "User Id\t\t\t\t\t\t\t\t\t\t\t\t:\t\t\t\t`$uid`",
            "User Name\t\t\t\t:\t\t\t\t`$username`",
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
            "`我的`  |  获取关于我的账号信息",
            "`说明`  |  在当前版本的使用说明",
            "`帮助`  |  在当前版本的使用帮助（指令列表）",
            "`汇率`  |  设置当前的汇率 | 汇率 [出账/进账/费率] [小数]",
            "`费率`  |  设置当前的费率 | 费率 [小数]",
            "`进账`  |  设置当前进账金额 | 进账 [小数]",
            "`出账`  |  设置当前出账金额 | 出账 [小数]",
            "`+`\t\t\t\t\t\t\t\t|  进账的别名用法",
            "`-`\t\t\t\t\t\t\t\t|  出账的别名用法",
            "`重置`  |  重置进出账数据 | 重置 [用户名:可选]",
            "`数据`  |  获取当日所有进出账数据 | 数据 [用户名:可选]",
            "`价格`  |  获取来自欧易的实时 `USDT` 兑换 `CNY(人民币)` 价格"
        ]);
    }
    
    
    /**
     * 设置汇率信息
     *
     * @param  array  $params
     * @param  int  $robotId
     * @return string
     */
    public static function rate(array $params, int $robotId): string
    {
        if (empty($params)) {
            return "参数错误";
        }
        
        if (count($params) !== 2) {
            return "参数不足";
        }
        
        $type = [
            '进账' => RobotsTrace::INCOMING_RATE,
            '出账' => RobotsTrace::PAYMENT_EXCHANGE_RATE,
            '费率' => RobotsTrace::RATING,
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
        
        Robots::query()
            ->where(RobotsTrace::T_UID, $robotId)
            ->update([
                $type[$params[0]] => $exchangeRate
            ]);
        
        return implode("\n", [
            "*设置成功！！！*",
            "当前为：`$exchangeRate`"
        ]);
    }
    
    /**
     * 设置费率
     *
     * @param  array  $params
     * @param $robotId
     * @return string
     */
    public static function rating(array $params, $robotId): string
    {
        if (empty($params)) {
            return "参数错误";
        }
        
        if (!is_numeric($params[0])) {
            return "参数类型错误";
        }
        
        $exchangeRate = (float) $params[0];
        
        Robots::query()
            ->where(RobotsTrace::T_UID, $robotId)
            ->update([
                RobotsTrace::RATING => $exchangeRate,
            ]);
        
        return implode("\n", [
            "*设置成功！！！*",
            "当前为：`$exchangeRate`"
        ]);
    }
    
    /**
     * 设置进账信息
     *
     * @param  array  $params
     * @param  string  $formUserName
     * @param  int  $tUID
     * @param  int  $robotId
     * @return string
     */
    public static function income(
        array $params,
        string $formUserName,
        int $tUID,
        int $robotId
    ): string {
        $billValidate = self::billValidate($params);
        if ($billValidate !== true) {
            return $billValidate;
        }
        
        $money = (float) $params[0];
        $model = Robots::query()
            ->where(RobotsTrace::T_UID, $robotId)
            ->first();
        $key = RobotsTrace::INCOMING_RATE;
        
        $exchangeRate = $model->$key;
        
        $model = Bill::query()->create([
            BillTrace::MONEY => $money,
            BillTrace::EXCHANGE_RATE => (float) $exchangeRate,
            BillTrace::TYPE => 1,
            BillTrace::T_UID => $tUID,
            BillTrace::USERNAME => $formUserName,
            BillTrace::ROBOT_ID => $robotId,
        ])->save();
        
        if ($model) {
            return self::dataMessage([], $robotId);
        }
        
        return "失败";
    }
    
    /**
     * 设置出账信息
     *
     * @param  array  $params
     * @param  string  $formUserName
     * @param  int  $tUID
     * @param  int  $robotId
     * @return string
     */
    public static function clearing(
        array $params,
        string $formUserName,
        int $tUID,
        int $robotId
    ): string {
        $billValidate = self::billValidate($params);
        if ($billValidate !== true) {
            return $billValidate;
        }
        
        $money = (float) $params[0];
        $model = Robots::query()
            ->where(RobotsTrace::T_UID, $robotId)
            ->first();
        $key = RobotsTrace::PAYMENT_EXCHANGE_RATE;
        
        $exchangeRate = $model->$key;
        
        $model = Bill::query()->create([
            BillTrace::MONEY => $money,
            BillTrace::EXCHANGE_RATE => (float) $exchangeRate,
            BillTrace::TYPE => -1,
            BillTrace::T_UID => $tUID,
            BillTrace::USERNAME => $formUserName,
            BillTrace::ROBOT_ID => $robotId,
        ])->save();
        
        if ($model) {
            return self::dataMessage([], $robotId);
        }
        
        return "失败";
    }
    
    /**
     * 重置指令
     *
     * @param  array  $params
     * @param  int  $robotId
     * @return string
     */
    public static function reset(array $params, int $robotId): string
    {
        $model = Bill::query()
            ->where(BillTrace::ROBOT_ID, $robotId);
        
        if (count($params) === 1) {
            $username = $params[0];
            $username = str_replace('@', '', $username);
            $model->where(BillTrace::USERNAME, $username);
        }
        
        $model->delete();
        
        return "重置成功！";
    }
    
    /**
     * 数据消息
     *
     * @param  array  $params
     * @param  int  $robotId
     * @return string
     */
    public static function dataMessage(array $params, int $robotId): string
    {
        // 进账数据
        $income = Bill::query()->whereBetween(BillTrace::CREATED_AT, [
            strtotime(date('Y-m-d').'00:00:00'),
            strtotime(date('Y-m-d').'23:59:59'),
        ])->where(
            BillTrace::ROBOT_ID,
            $robotId
        )->where(
            'type',
            1
        );
        
        // 出账数据
        $clearing = Bill::query()->whereBetween(BillTrace::CREATED_AT, [
            strtotime(date('Y-m-d').'00:00:00'),
            strtotime(date('Y-m-d').'23:59:59'),
        ])->where(
            BillTrace::ROBOT_ID,
            $robotId
        )->where(
            'type',
            -1
        );
        
        if (count($params) === 1) {
            $username = $params[0];
            $username = str_replace('@', '', $username);
            
            $income = $income->where(BillTrace::USERNAME, $username);
            $clearing = $clearing->where(BillTrace::USERNAME, $username);
        }
        
        $income = $income->get()->toArray();
        $clearing = $clearing->get()->toArray();
        
        $messages = [];
        $formMessage = [];
        
        $formMessage = self::build($formMessage, $income, 'income', $robotId);
        $formMessage = self::build($formMessage, $clearing, 'clearing', $robotId);
        
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
     * 导出数据
     *
     * @param  int  $robotId
     * @return string
     */
    public static function export(int $robotId): string
    {
        $exportData = new BaseBillExport(
            [
                'ID',
                '11',
                '11',
            ]
        );
        
        mt_srand();
        $file_id = time().'_'.mt_rand(100, 999);
        
        $save = $exportData->store(
            "/$robotId/excel/$file_id.csv",
            'local',
            ExcelType::CSV
        );
        
        return $save ? '导出成功' : '导出失败';
    }
    
    /**
     * 构建数据字符串
     *
     * @param  array  $formMessage
     * @param  array  $data
     * @param  string  $key
     * @param  int  $robotId
     * @return array
     */
    public static function build(
        array $formMessage,
        array $data,
        string $key,
        int $robotId
    ): array {
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
            
            $model = Robots::query()
                ->where(RobotsTrace::T_UID, $robotId)
                ->first();
            $ratingKey = RobotsTrace::RATING;
            $paymentExchangeRateKey = RobotsTrace::PAYMENT_EXCHANGE_RATE;
            
            $rating = (float) $model->$ratingKey;
            $paymentExchangeRate = (float) $model->$paymentExchangeRateKey;
            
            // 出账数据修正
            if ($key === 'clearing') {
                $exchangeRate = $paymentExchangeRate;
            }
            
            $difference = 0;
            
            // 数学计算
            if (!empty($money) && !empty($exchangeRate)) {
                if ($key === 'income') {
                    $difference = $money * $rating / $exchangeRate;
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
                $messageString .= "$money\*$rating/$exchangeRate=$difference";
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
        
        return true;
    }
    
}