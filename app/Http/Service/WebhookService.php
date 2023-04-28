<?php

namespace App\Http\Service;

use App\Models\Bill;
use App\Models\Trace\BillTrace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        
        if (!is_numeric($params[0])) {
            return "参数类型错误";
        }
        
        $exchangeRate = (float) $params[0];
        
        if ($exchangeRate <= 0) {
            return "汇率必须大于0";
        }
        
        $cache = Cache::put('exchange_rate', $exchangeRate);
        
        if ($cache) {
            $exchangeRate = Cache::get('exchange_rate');
            return implode("\n", [
                "*汇率设置成功！！！*",
                "当前汇率为：`$$exchangeRate`"
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
        
        $exchangeRate = Cache::get('exchange_rate', false);
        if ($exchangeRate === false) {
            return "汇率未设置";
        }
        
        $model = Bill::query()->create([
            BillTrace::MONEY => $money,
            BillTrace::EXCHANGE_RATE => $exchangeRate,
            BillTrace::TYPE => 1,
            BillTrace::T_UID => $tUID,
            BillTrace::USERNAME => $formUserName,
        ])->save();
        
        if ($model) {
            $income = Bill::query()->whereBetween(BillTrace::CREATED_AT, [
                strtotime(date('Y-m-d').'00:00:00'),
                strtotime(date('Y-m-d').'23:59:59'),
            ])->where('type', 1)->get()->toArray();
            
            $message = ["*进账成功！！！*"];
            $message[] = "#######################################################";
            $message[] = "*进账：*";
            
            foreach ($income as $item) {
                $date = date('H:i:s', (int) $item[BillTrace::CREATED_AT]);
                
                $money = $item[BillTrace::MONEY];
                $money = (float) $money;
                $exchangeRate = (float) $item[BillTrace::EXCHANGE_RATE];
                $money = number_format($money, 2);
                $exchangeRate = number_format($exchangeRate, 2);
                
                if (empty($money) || empty($exchangeRate)) {
                    $difference = 0;
                } else {
                    $difference = $money / $exchangeRate;
                    $difference = number_format($difference, 2);
                }
                
                $moneyString = str_replace('.', "\\.", $money);
                $differenceString = str_replace('.', "\\.", (string) $difference);
                $exchangeRateString = str_replace('.', "\\.", $exchangeRate);
                $username = $item[BillTrace::USERNAME];
                
                $messageString = "`\\[$date\\]`  \\|  ";
                $messageString .= "$moneyString/$exchangeRateString\\=$differenceString  \\|  ";
                $messageString .= "@$username";
                
                $message[] = $messageString;
            }
            
            return implode("\n", $message);
        }
        
        return "失败";
    }
    
    public static function clearing(array $params): string
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
        
        return "";
    }
    
}