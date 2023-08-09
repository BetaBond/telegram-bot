<?php

namespace App\Models\Trace;

use Bond\Ryo\Trace\TraceEloquent;

/**
 * 追踪类 (帮助 ied 更好的发现)
 *
 * @author beta
 */
class WalletTrace extends TraceEloquent
{

    /**
     * 表名称
     *
     * @var string
     */
    const TABLE = 'wallet';

    /**
     * 钱包 ID
     *
     * @var string
     */
    const ID = 'id';

    /**
     * 所有者 ID
     *
     * @var string
     */
    const T_UID = 't_uid';

    /**
     * 钱包名称
     *
     * @var string
     */
    const NAME = 'name';

    /**
     * 钱包余额
     *
     * @var string
     */
    const BALANCE = 'balance';

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
