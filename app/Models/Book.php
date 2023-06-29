<?php

namespace App\Models;

use App\Models\Trace\BookTrace as Trace;

/**
 * 账本模型类
 *
 * @author southwan
 */
class Book extends Model
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
    
}