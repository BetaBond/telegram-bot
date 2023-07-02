<?php

namespace App\Http\Robots;

use App\Exports\BaseBillExport;
use App\Helpers\MessageHelper;
use App\Models\Auth;
use App\Models\Book;
use App\Models\Robots;
use App\Models\Trace\AuthTrace;
use App\Models\Trace\BookTrace;
use App\Models\Trace\RobotsTrace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel as ExcelType;
use Psr\SimpleCache\InvalidArgumentException;
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
     * 记录账本信息
     *
     * @param  array  $params
     * @param  string  $formUserName
     * @param  int  $tUID
     * @param  int  $robotId
     * @param  bool  $income
     *
     * @return string
     */
    private static function record(
        array $params,
        string $formUserName,
        int $tUID,
        int $robotId,
        bool $income = true
    ): string {
        $parameterCalibration = MessageHelper::parameterCalibration($params, 1);
        
        if ($parameterCalibration !== true) {
            return $parameterCalibration;
        }
        
        if (!is_numeric($params[0])) {
            return "参数类型错误";
        }
        
        $money = (float) $params[0];
        
        if ($money <= 0) {
            return "参数必须大于0";
        }
        
        $model = Robots::query()
            ->where(RobotsTrace::T_UID, $robotId)
            ->first();
        
        $rateKey = RobotsTrace::INCOMING_RATE;
        $exchangeRateKey = RobotsTrace::INCOME_EXCHANGE_RATE;
        
        if (!$income) {
            $rateKey = RobotsTrace::CLEARING_RATE;
            $exchangeRateKey = RobotsTrace::CLEARING_EXCHANGE_RATE;
        }
        
        $rate = $model->$rateKey;
        $exchangeRate = $model->$exchangeRateKey;
        
        $model = Book::query()->create([
            BookTrace::MONEY         => $money,
            BookTrace::EXCHANGE_RATE => (float) $exchangeRate,
            BookTrace::RATE          => (float) $rate,
            BookTrace::TYPE          => $income ? 1 : -1,
            BookTrace::T_UID         => $tUID,
            BookTrace::USERNAME      => $formUserName,
            BookTrace::ROBOT_ID      => $robotId,
        ])->save();
        
        if ($model) {
            return self::dataMessage([], $robotId);
        }
        
        return "失败";
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
            '我的' => self::mine(
                $messageInfo['form_id'],
                $messageInfo['form_user_name']
            ),
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
                    '单价' => self::price(),
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
            "",
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
            "`汇率`  |  设置当前的汇率 | 汇率 [下发/入款] [小数]",
            "`费率`  |  设置当前的费率 | 费率 [下发/入款] [小数]",
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
        return self::record(
            $params,
            $formUserName,
            $tUID,
            $robotId
        );
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
        return self::record(
            $params,
            $formUserName,
            $tUID,
            $robotId,
            false
        );
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
        $model = Book::query()
            ->where(BookTrace::ROBOT_ID, $robotId);
        
        if (count($params) === 1) {
            $username = $params[0];
            $username = str_replace('@', '', $username);
            $model->where(BookTrace::USERNAME, $username);
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
        // 查询所有数据
        $books = Book::query()->whereBetween(BookTrace::CREATED_AT, [
            strtotime(date('Y-m-d').'00:00:00'),
            strtotime(date('Y-m-d').'23:59:59'),
        ])->where(
            BookTrace::ROBOT_ID,
            $robotId
        );
        
        // 条件筛选
        if (count($params) === 1) {
            $username = $params[0];
            $username = str_replace('@', '', $username);
            
            $books = $books->where(BookTrace::USERNAME, $username);
        }
        
        // 取出数据
        $books = $books->get()->toArray();
        
        $incomeDataArray = [];
        $clearingDataArray = [];
        
        // 遍历筛选计算
        foreach ($books as $book) {
            // 取出参数并加工
            $money = (float) $book[BookTrace::MONEY];
            $rate = (float) $book[BookTrace::RATE];
            $exchangeRate = (float) $book[BookTrace::EXCHANGE_RATE];
            $date = date('H:i:s', (int) $book[BookTrace::CREATED_AT]);
            $uuid = $book[BookTrace::ID];
            $username = $book[BookTrace::USERNAME];
            $type = $book[BookTrace::TYPE];
            
            $uuidEnd = substr($uuid, -3, 3);
            $uuidMain = substr($uuid, 0, strlen($uuid) - 3);
            $uuidMain = date('His', (int) $uuidMain);
            $uuid = $uuidEnd.$uuidMain;
            
            // 添加唯一ID和日期
            $msgString = "[`$uuid`] [`$date`]\n";
            
            // 构建数据数组
            $buildDataArray = function (
                array $dataArray,
                string $username,
                string $msgString,
                float $result,
                float $money
            ) {
                $dataArray[$username]['strings'][] = $msgString;
                
                if (!isset($dataArray[$username]['total'])) {
                    $dataArray[$username]['total'] = 0;
                }
                
                if (!isset($dataArray[$username]['money'])) {
                    $dataArray[$username]['money'] = 0;
                }
                
                $dataArray[$username]['total'] += $result;
                $dataArray[$username]['money'] += $money;
                
                return $dataArray;
            };
            
            // 进账的构造
            if ($type === 1) {
                $result = ($money * $rate) / $exchangeRate;
                $result = round($result, 2);
                $msgString .= "[`($money \* $rate) / $exchangeRate = $result`]\n";
                
                $incomeDataArray = $buildDataArray(
                    $incomeDataArray,
                    $username,
                    $msgString,
                    $result,
                    $money
                );
            }
            
            // 出账的构造
            if ($type === -1) {
                $result = $money / ($exchangeRate - $rate);
                $result = round($result, 2);
                $msgString .= "[`$money / ($exchangeRate - $rate) = $result`]\n";
                
                $clearingDataArray = $buildDataArray(
                    $clearingDataArray,
                    $username,
                    $msgString,
                    $result,
                    $money
                );
            }
            
        }
        
        // 计算合计数量
        $totalNumber = function (array $dataArray) {
            $total = 0;
            
            foreach ($dataArray as $item) {
                $total += count($item['strings']);
            }
            
            return $total;
        };
        
        // 构建账本消息
        $buildMessage = function (array $dataArray) {
            $messages = [];
            
            foreach ($dataArray as $username => $value) {
                $formSting = '来自 @'.$username.'（';
                $formSting .= count($value['strings'])." 笔）：\n";
                $messages[] = $formSting;
                $messages = array_merge($messages, $value['strings']);
            }
            
            return $messages;
        };
        
        // 计算合计金额
        $totalMoney = function (array $dataArray) {
            $total = 0;
            $money = 0;
            
            foreach ($dataArray as $item) {
                $total += $item['total'];
                $money += $item['money'];
            }
            
            return "[`￥$money` / `₮$total`]";
        };
        
        // 构造输出字符
        $messages[] = '入款（'.$totalNumber($incomeDataArray).' 笔）：';
        $messages[] = '';
        
        // 构建进账字符信息
        $messages = array_merge(
            $messages,
            $buildMessage($incomeDataArray)
        );
        
        $messages[] = '';
        $messages[] = '合计入款：'.$totalMoney($incomeDataArray);
        $messages[] = '';
        $messages[] = '下发（'.$totalNumber($clearingDataArray).' 笔）：';
        $messages[] = '';
        
        // 构建出账字符信息
        $messages = array_merge(
            $messages,
            $buildMessage($clearingDataArray)
        );
        
        $messages[] = '';
        $messages[] = '合计下发：'.$totalMoney($clearingDataArray);
        
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
    
    /**
     * 获取机器人信息
     *
     * @param  Api  $telegram
     *
     * @return string
     */
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
        
        $exists = Book::query()->where('id', $sid)->exists();
        
        if (!$exists) {
            return '记录不存在！';
        }
        
        $model = Book::query()->where('id', $sid)->delete();
        
        return $model === 1 ? '回撤成功！' : '回撤失败';
    }
    
    /**
     * 单价信息
     *
     * @return string
     */
    public static function price(): string
    {
        $store = Cache::store('redis');
        
        try {
            $store = $store->get('okx_usdt_block_trade');
            $timestamp = $store->get('okx_usdt_block_trade_updated', null);
            $time = empty($timestamp) ? '未同步' : date('Y-m-d H:i:s', $timestamp);
        } catch (InvalidArgumentException $e) {
            Log::error($e->getMessage());
            return "错误！";
        }
        
        $messages = ["*当前欧易最优买卖价格：*"];
        $messages[] = '';
        $messages[] = "*买入方向(TOP10)：[$time]*";
        $messages[] = '';
        
        $prices = json_decode($store, true);
        $prices = is_array($prices) ? $prices : [];
        
        foreach ($prices as $key => $price) {
            $messages[] = "[`$key`]\t\t:\t\t`￥$price`";
        }
        
        return implode("\n", $messages);
    }
    
}