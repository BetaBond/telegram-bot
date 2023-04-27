<?php

namespace Bond\Ryo\Support;

use DateTimeImmutable;

/**
 * 時間戳 - [Timestamp]
 *
 * @author beta
 */
class Timestamp
{
    
    /**
     * 秒級時間戳
     *
     * @return int
     */
    public static function second(): int
    {
        return time();
    }
    
    /**
     * 毫秒級時間戳
     *
     * @return int
     */
    public static function millisecond(): int
    {
        return (int) (microtime(true) * 1000);
    }
    
    /**
     * 生成有效期時間戳
     *
     * @param  int  $timestamp
     * @param  int  $second
     * @return DateTimeImmutable
     */
    public static function validity(int $timestamp, int $second): DateTimeImmutable
    {
        return (new DateTimeImmutable())
            ->setTimestamp($timestamp + $second);
    }
    
}