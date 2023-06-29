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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelType;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\FileUpload\InputFile;

/**
 * 基础型账本机器人
 *
 * @author southwan
 */
class BaseBillRobot
{
    
    /**
     * 处理汇率/费率信息
     *
     * @param  array  $params
     * @param  int  $robotId
     * @param  array  $types
     *
     * @return string
     */
    private static function rate(
        array $params,
        int $robotId,
        array $types
    ): string {
        $parameterCalibration = MessageHelper::parameterCalibration($params, 2);
        
        if ($parameterCalibration !== true) {
            return $parameterCalibration;
        }
        
        if (!in_array($params[0], array_keys($types))) {
            return "第一个参数必须是[入款 | 下发]其中之一";
        }
        
        if (!is_numeric($params[1])) {
            return "参数类型错误";
        }
        
        $exchangeRate = (float) $params[1];
        
        if ($exchangeRate <= 0) {
            return "参数必须大于0";
        }
        
        Robots::query()
            ->where(RobotsTrace::T_UID, $robotId)
            ->update([
                $types[$params[0]] => $exchangeRate
            ]);
        
        return implode("\n", [
            "*设置成功！！！*",
            "当前为：`$exchangeRate`"
        ]);
    }
    
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
        $message = match ($command) {
            '我的' => self::mine($messageInfo['form_id'],
                $messageInfo['form_user_name']),
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
                    '汇率' => self::exchange_rate($params, $robot->id),
                    '费率' => self::rating($params, $robot->id),
                    '入款', '+' => self::income(
                        $params,
                        $messageInfo['form_user_name'],
                        $messageInfo['form_id'],
                        $robot->id
                    ),
                    '下发', '-' => self::clearing(
                        $params,
                        $messageInfo['form_user_name'],
                        $messageInfo['form_id'],
                        $robot->id
                    ),
                    '重置' => self::reset($params, $robot->id),
                    '数据' => self::dataMessage($params, $robot->id),
                    '导出' => self::export(
                        $telegram,
                        $messageInfo['chat_id'],
                        $robot->id
                    ),
                    '信息' => self::info($telegram),
                    '回撤' => self::repeal($params),
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
                'chat_id'    => $messageInfo['chat_id'],
                'parse_mode' => 'MarkdownV2',
                'text'       => $message
            ]);
        }
        
        return true;
    }
    
    /**
     * 获取关于我的所有信息
     *
     * @param  int  $uid
     * @param  string  $username
     *
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
            "`汇率`  |  设置当前的汇率 | 汇率 [下发/入款/费率] [小数]",
            "`费率`  |  设置当前的费率 | 费率 [小数]",
            "`入款`  |  设置当前进账金额 | 入款 [小数]",
            "`下发`  |  设置当前出账金额 | 下发 [小数]",
            "`+`\t\t\t\t\t\t\t\t|  进账的别名用法",
            "`-`\t\t\t\t\t\t\t\t|  出账的别名用法",
            "`重置`  |  重置进出账数据 | 重置 [用户名:可选]",
            "`数据`  |  获取当日所有进出账数据 | 数据 [用户名:可选]",
            "`回撤`  |  回撤填写错误的数据 | 回撤 [SID]"
        ]);
    }
    
    /**
     * 设置汇率信息
     *
     * @param  array  $params
     * @param  int  $robotId
     *
     * @return string
     */
    public static function exchange_rate(array $params, int $robotId): string
    {
        return self::rate($params, $robotId, [
            '入款' => RobotsTrace::INCOME_EXCHANGE_RATE,
            '下发' => RobotsTrace::CLEARING_EXCHANGE_RATE,
        ]);
    }
    
    /**
     * 设置汇率信息
     *
     * @param  array  $params
     * @param  int  $robotId
     *
     * @return string
     */
    public static function rating(array $params, int $robotId): string
    {
        return self::rate($params, $robotId, [
            '入款' => RobotsTrace::INCOMING_RATE,
            '下发' => RobotsTrace::CLEARING_RATE,
        ]);
    }
    
    /**
     * 设置进账信息
     *
     * @param  array  $params
     * @param  string  $formUserName
     * @param  int  $tUID
     * @param  int  $robotId
     *
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
        $key = RobotsTrace::INCOME_EXCHANGE_RATE;
        
        $exchangeRate = $model->$key;
        
        $model = Bill::query()->create([
            BillTrace::MONEY         => $money,
            BillTrace::EXCHANGE_RATE => (float) $exchangeRate,
            BillTrace::TYPE          => 1,
            BillTrace::T_UID         => $tUID,
            BillTrace::USERNAME      => $formUserName,
            BillTrace::ROBOT_ID      => $robotId,
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
     *
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
        $key = RobotsTrace::CLEARING_EXCHANGE_RATE;
        
        $exchangeRate = $model->$key;
        
        $model = Bill::query()->create([
            BillTrace::MONEY         => $money,
            BillTrace::EXCHANGE_RATE => (float) $exchangeRate,
            BillTrace::TYPE          => -1,
            BillTrace::T_UID         => $tUID,
            BillTrace::USERNAME      => $formUserName,
            BillTrace::ROBOT_ID      => $robotId,
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
     *
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
     *
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
        $formMessage = self::build($formMessage, $clearing, 'clearing',
            $robotId);
        
        $messages[] = '入款（'.count($income).' 笔）：';
        $messages[] = '';
        
        $incomeMoney = 0;
        $clearingMoney = 0;
        $rate = [
            'income'   => 1,
            'clearing' => 1,
            'rating'   => 1,
        ];
        
        // 构建进账字符信息
        foreach ($formMessage as $items) {
            if (isset($items['income'])
                && !empty($items['income']['messages'])
            ) {
                $messages[] = '来自 @'.$items['username'].'（'
                    .count($items['income']['messages']).' 笔）：';
                
                $rate['income'] = $items['income']['rate'];
                $rate['rating'] = $items['income']['rating'];
                
                foreach ($items['income']['messages'] as $item) {
                    $messages[] = $item;
                }
                
                $messages[] = '';
                $incomeMoney += $items['income']['money'];
            }
        }
        
        $incomeMoneyInfo = [];
        $incomeMoneyInfo['cny'] = $incomeMoney;
        $incomeMoneyInfo['usdt'] = ($incomeMoney * $rate['rating'])
            / $rate['income'];
        $incomeMoneyInfo['usdt'] = round($incomeMoneyInfo['usdt'], 2);
        $incomeMoneyInfo['string'] = "[`￥".$incomeMoneyInfo['cny']."` / ";
        $incomeMoneyInfo['string'] .= "`₮".$incomeMoneyInfo['usdt']."`]";
        $messages[] = "合计入款：".$incomeMoneyInfo['string'];
        $messages[] = '';
        
        $messages[] = '下发（'.count($clearing).' 笔）：';
        $messages[] = '';
        
        // 构建出账信息
        foreach ($formMessage as $items) {
            if (isset($items['clearing'])
                && !empty($items['clearing']['messages'])
            ) {
                $messages[] = '来自 @'.$items['username'].'（'
                    .count($items['clearing']['messages']).' 笔）：';
                
                $rate['clearing'] = $items['clearing']['rate'];
                
                foreach ($items['clearing']['messages'] as $item) {
                    $messages[] = $item;
                }
                $messages[] = '';
                $clearingMoney += $items['clearing']['money'];
            }
        }
        
        $clearingMoneyInfo = [];
        $clearingMoneyInfo['cny'] = $clearingMoney;
        $clearingMoneyInfo['usdt'] = $clearingMoney / ($rate['clearing']
                - 0.045);
        $clearingMoneyInfo['usdt'] = round($clearingMoneyInfo['usdt'], 2);
        $clearingMoneyInfo['string'] = "[`￥".$clearingMoneyInfo['cny']."` / ";
        $clearingMoneyInfo['string'] .= "`₮".$clearingMoneyInfo['usdt']."`]";
        $messages[] = "合计下发：".$clearingMoneyInfo['string'];
        $messages[] = '';
        
        $messages[] = '总计：	[ `￥'.($incomeMoney - $clearingMoney).'` ]';
        
        return implode("\n", $messages);
    }
    
    /**
     * 导出数据
     *
     * @param  Api  $telegram
     * @param  int  $chatId
     * @param  int  $robotId
     *
     * @return string
     */
    public static function export(
        Api $telegram,
        int $chatId,
        int $robotId
    ): string {
        $exportData = new BaseBillExport(
            [
                ['ID',],
                ['ID',],
                ['ID',],
            ]
        );
        
        $directory = "/$robotId";
        $path = public_path($directory);
        
        if (File::isDirectory($path)) {
            if (!File::makeDirectory($directory, 0777, true, true)) {
                return '导出失败！';
            }
        }
        
        mt_srand();
        $file_id = time().'_'.mt_rand(100, 999);
        $fileName = "$file_id.csv";
        $file = "$directory/$fileName";
        
        $save = $exportData->store(
            $file,
            'public',
            ExcelType::CSV
        );
        
        $inputFile = InputFile::create(
            Storage::disk('public')->readStream($file),
            $fileName,
        );
        
        try {
            $telegram->sendDocument([
                'chat_id'  => $chatId,
                'document' => $inputFile,
            ]);
        } catch (TelegramSDKException $e) {
            Log::error($e->getMessage());
            return "导出失败";
        }
        
        return $save ? '导出成功' : '导出失败';
    }
    
    public static function info(Api $telegram): string
    {
        try {
            $robot = $telegram->getMe();
            $telegram->getWebhookInfo();
        } catch (TelegramSDKException $e) {
            Log::error($e->getMessage());
            return '获取失败';
        }
        
        $messages = [
            '*机器人信息：*',
            "名称  :  [ `$robot->firstName` ]",
            "唯一标识  :  [ `$robot->id` ]",
            "账号  :  [ `$robot->username` ]",
            '加入群组  :  '.($robot->canJoinGroups ? '允许' : '不允许'),
            '阅读所有的群组信息  :  '.($robot->canReadAllGroupMessages ? '允许'
                : '不允许'),
            '内联查询  :  '.($robot->supportsInlineQueries ? '支持' : '不支持'),
        ];
        
        return implode("\n", $messages);
    }
    
    /**
     * 回撤数据
     *
     * @param  array  $params
     *
     * @return string
     */
    public static function repeal(array $params): string
    {
        $parameterCalibration = MessageHelper::parameterCalibration($params, 1);
        
        if ($parameterCalibration !== true) {
            return $parameterCalibration;
        }
        
        $sid = $params[0];
        
        $sidEnd = substr($sid, 0, 3);
        $sidMain = substr($sid, 3, strlen($sid) - 3);
        $sidMain = strtotime(date('Ymd ').$sidMain);
        
        $sid = $sidMain.$sidEnd;
        
        $exists = Bill::query()->where('id', $sid)->exists();
        
        if (!$exists) {
            return '记录不存在！';
        }
        
        $model = Bill::query()->where('id', $sid)->delete();
        
        return $model === 1 ? '回撤成功！' : '回撤失败';
    }
    
    /**
     * 构建数据字符串
     *
     * @param  array  $formMessage
     * @param  array  $data
     * @param  string  $key
     * @param  int  $robotId
     *
     * @return array
     */
    public static function build(
        array $formMessage,
        array $data,
        string $key,
        int $robotId
    ): array {
        foreach ($data as $item) {
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
            
            $ratingKey = RobotsTrace::INCOMING_RATE;
            $paymentExchangeRateKey = RobotsTrace::CLEARING_EXCHANGE_RATE;
            
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
            $formMessage[$item[BillTrace::T_UID]][$key]['money'] += $money;
            
            $uuid = $item[BillTrace::ID];
            
            $uuidEnd = substr($uuid, -3, 3);
            $uuidMain = substr($uuid, 0, strlen($uuid) - 3);
            $uuidMain = date('His', (int) $uuidMain);
            $uuid = $uuidEnd.$uuidMain;
            
            // 构建字符串
            $messageString = "[`$uuid`] [`$date`]  ";
            
            if ($key === 'income') {
                $messageString .= "$money\*$rating/$exchangeRate=$difference";
            }
            
            if ($key === 'clearing') {
                $messageString .= "$money/($exchangeRate-0.045)=$difference";
            }
            
            $t_uid_key = $item[BillTrace::T_UID];
            $formMessage[$t_uid_key]['username'] = $username;
            $formMessage[$t_uid_key][$key]['rate'] = $exchangeRate;
            $formMessage[$t_uid_key][$key]['rating'] = $rating;
            $formMessage[$t_uid_key][$key]['messages'][] = $messageString;
        }
        
        return $formMessage;
    }
    
    /**
     * 账单参数验证
     *
     * @param  array  $params
     *
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