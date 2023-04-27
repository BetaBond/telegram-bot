<?php

namespace App\Models;

use App\Models\Trace\BillTrace as Trace;

/**
 * 账单模型类
 *
 * @author southwan
 */
class Bill extends Model
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