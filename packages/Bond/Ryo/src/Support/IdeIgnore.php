<?php

namespace Bond\Ryo\Support;

/**
 * 幫助忽略 [IDE] 某些提示
 *
 * @author beta
 */
class IdeIgnore
{
    
    /**
     * 忽略未使用參數
     *
     * @param  mixed  $field
     */
    public static function noUseParam(mixed ...$field): void
    {
        // ...
    }
    
}