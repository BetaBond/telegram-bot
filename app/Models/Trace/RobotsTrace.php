<?php

namespace App\Models\Trace;

use Bond\Ryo\Trace\TraceEloquent;

/**
 * 追踪类 (帮助 ied 更好的发现)
 *
 * @author beta
 */
class RobotsTrace extends TraceEloquent
{
    
    /**
     * 表名称
     *
     * @var string
     */
    const TABLE = 'robots';
    
    /**
     * 机器人 ID
     *
     * @var string
     */
    const ID = 'id';
    
    /**
     * 机器人凭证
     *
     * @var string
     */
    const TOKEN = 'token';
    
    /**
     * 入账汇率
     *
     * @var string
     */
    const INCOMING_RATE = 'incoming_rate';
    
    /**
     * 出账汇率
     *
     * @var string
     */
    const PAYMENT_EXCHANGE_RATE = 'payment_exchange_rate';
    
    /**
     * 费率
     *
     * @var string
     */
    const RATING = 'rating';
    
    /**
     * 创建时间
     *
     * @var string
     */
    const CREATED_AT = 'created_at';
    
    /**
     * 更新时间
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';
    
    /**
     * 到期时间
     *
     * @var string
     */
    const EXPIRE_AT = 'expire_at';
    
    /**
     * 隐藏列
     *
     * @var array
     */
    const HIDE = [];
    
}