<?php

namespace App\Http\Service;

use App\Models\Bill;
use App\Models\Trace\BillTrace;
use Illuminate\Support\Facades\Cache;

/**
 * Webhook 服务类
 *
 * @author southwan
 */
class WebhookService
{
    
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
            "`说明`  \\|  在当前版本的使用说明",
            "`帮助`  \\|  在当前版本的使用帮助（指令列表）",
            "`汇率`  \\|  设置当前的汇率 \\| 汇率 \\[汇率\\|小数\\]",
            "`进账`  \\|  设置当前进账金额 \\| 进账 \\[金额\\|小数\\]",
            "`出账`  \\|  设置当前出账金额 \\| 出账 \\[金额\\|小数\\]",
        ]);
    }
    
    /**
     * 设置汇率信息
     *
     * @param  array  $params
     * @return string
     */
    public static function exchangeRate(array $params): string
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
        $exchangeRate = Cache::get('exchange_rate', false);
        
        $model = Bill::query()->create([
            BillTrace::MONEY => $money,
            BillTrace::EXCHANGE_RATE => $exchangeRate,
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
        $exchangeRate = Cache::get('exchange_rate', false);
        
        $model = Bill::query()->create([
            BillTrace::MONEY => $money,
            BillTrace::EXCHANGE_RATE => $exchangeRate,
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
        
        $cny = 0;
        $usd = 0;
        
        // 构建进账字符信息
        foreach ($formMessage as $items) {
            if (isset($items['income']) && !empty($items['income']['messages'])) {
                $messages[] = '来自 @'.$items['username'].'（'.count($items['income']['messages']).' 笔）：';
                foreach ($items['income']['messages'] as $item) {
                    $messages[] = $item;
                }
                $messages[] = '';
                $cny += $items['income']['cny'];
                $usd += $items['income']['usd'];
            }
        }
        
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
                $cny -= $items['clearing']['cny'];
                $usd -= $items['clearing']['usd'];
            }
        }
        
        $cny = number_format($cny, 2);
        $usd = number_format($usd, 2);
        $messages[] = "合计进账：[`$$usd`]  [`￥$cny`]";
        
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
            if (!isset($formMessage[$item[BillTrace::T_UID]][$key]['usd'])) {
                $formMessage[$item[BillTrace::T_UID]][$key]['usd'] = 0;
            }
            
            if (!isset($formMessage[$item[BillTrace::T_UID]][$key]['cny'])) {
                $formMessage[$item[BillTrace::T_UID]][$key]['cny'] = 0;
            }
            
            $username = $item[BillTrace::USERNAME];
            $date = date('H:i:s', (int) $item[BillTrace::CREATED_AT]);
            $money = (float) $item[BillTrace::MONEY];
            $exchangeRate = (float) $item[BillTrace::EXCHANGE_RATE];
            
            $incomeExchangeRate = Cache::get('income_exchange_rate', false);
            $clearingExchangeRate = Cache::get('clearing_exchange_rate', false);
            
            $difference = 0;
            
            // 数学计算
            if (!empty($money) && !empty($exchangeRate)) {
                if ($key === 'income') {
                    $difference = $money * $incomeExchangeRate / $exchangeRate;
                }
                
                if ($key === 'clearing') {
                    $difference = $money / $clearingExchangeRate - 0.045;
                }
            }
            
            $formMessage[$item[BillTrace::T_UID]][$key]['usd'] += $difference;
            $formMessage[$item[BillTrace::T_UID]][$key]['cny'] += $money;
            
            // 精度调整
            $money = number_format($money, 2);
            $exchangeRate = number_format($exchangeRate, 2);
            $difference = number_format($difference, 2);
            
            // 构建字符串
            $messageString = "[`$date`]  ";
            
            if ($key === 'income') {
                $messageString .= "$money//*$incomeExchangeRate/$exchangeRate=$difference  ";
            }
            
            if ($key === 'clearing') {
                $messageString .= "$money/$clearingExchangeRate-0.045=$difference  ";
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