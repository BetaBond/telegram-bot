<?php

namespace App\Models\Trace;

use Bond\Ryo\Trace\TraceEloquent;

/**
 * 追踪类 (帮助 ied 更好的发现)
 *
 * @author beta
 */
class AuthTrace extends TraceEloquent
{
    
    /**
     * 表名称
     *
     * @var string
     */
    const TABLE = 'robots';
    
    /**
     * 授权 ID
     *
     * @var string
     */
    const ID = 'id';
    
    /**
     * Telegram UID
     *
     * @var string
     */
    const T_UID = 't_uid';
    
    /**
     * Telegram 用户名
     *
     * @var string
     */
    const USERNAME = 'username';
    
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
     * 隐藏列
     *
     * @var array
     */
    const HIDE = [];
    
}