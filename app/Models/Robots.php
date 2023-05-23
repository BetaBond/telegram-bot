<?php

namespace App\Models;

use App\Models\Trace\RobotsTrace as Trace;

/**
 * 机器人信息模型类
 *
 * @author beta
 */
class Robots extends Model
{
    
    /**
     * 创建一个新的 [Eloquent] 模型实例
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->trace = new Trace();
        
        parent::__construct($attributes);
    }
    
}