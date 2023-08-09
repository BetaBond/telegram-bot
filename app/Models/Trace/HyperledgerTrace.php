<?php

namespace App\Models\Trace;

use Bond\Ryo\Trace\TraceEloquent;

/**
 * 追踪类 (帮助 ied 更好的发现)
 *
 * @author beta
 */
class HyperledgerTrace extends TraceEloquent
{

    /**
     * 表名称
     *
     * @var string
     */
    const TABLE = 'hyperledger';

    /**
     * 账本 ID
     *
     * @var string
     */
    const ID = 'id';

    /**
     * 账单类型
     *
     * @var string
     */
    const TYPE = 'type';

    /**
     * Telegram UID
     *
     * @var string
     */
    const T_UID = 't_uid';

    /**
     * 机器人 ID
     *
     * @var string
     */
    const ROBOT_ID = 'robot_id';

    /**
     * 钱包 ID
     *
     * @var string
     */
    const WALLET_ID = 'wallet_id';

    /**
     * 用户名
     *
     * @var string
     */
    const USERNAME = 'username';

    /**
     * 金额
     *
     * @var string
     */
    const MONEY = 'money';

    /**
     * 汇率
     *
     * @var string
     */
    const EXCHANGE_RATE = 'exchange_rate';

    /**
     * 费率
     *
     * @var string
     */
    const RATE = 'rate';

    /**
     * 备注
     *
     * @var string
     */
    const REMARK = 'remark';

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
