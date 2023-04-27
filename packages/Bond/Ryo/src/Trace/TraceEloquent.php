<?php

namespace Bond\Ryo\Trace;

use ReflectionClass;

/**
 * 追鐘 [laravel Eloquent ORM]
 *
 * @author beta
 */
abstract class TraceEloquent
{
    
    /**
     * 表名称
     *
     * @var string
     */
    const TABLE = 'bill';
    
    /**
     * ID
     *
     * @var string
     */
    const ID = 'id';
    
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
    
    /**
     * 獲取所有子類常量
     *
     * @return array
     */
    private static function getConstants(): array
    {
        return (new ReflectionClass(get_called_class()))
            ->getConstants();
    }
    
    /**
     * 獲取所有列名稱
     *
     * @return array
     */
    public static function getAllColumns(): array
    {
        $constants = self::getConstants();
        
        return array_filter($constants, function (string $key) {
            return !in_array($key, ['TABLE', 'HIDE']);
        }, ARRAY_FILTER_USE_KEY);
    }
    
}