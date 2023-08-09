<?php

namespace App\Models;

use App\Models\Trace\AuthTrace as Trace;
use App\Models\Trace\RobotsTrace;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 授权模型类
 *
 * @author southwan
 */
class Auth extends Model
{

    /**
     * 创建一个新的 [Eloquent] 模型实例
     *
     * @param  array  $attributes
     *
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->trace = new Trace();

        parent::__construct($attributes);
    }

    /**
     * 绑定机器人信息表 [一对一关联]
     *
     * @return HasOne
     */
    public function robot(): HasOne
    {
        return $this->hasOne(
            Robots::class,
            RobotsTrace::T_UID,
            Trace::ROBOT_ID
        );
    }

}
