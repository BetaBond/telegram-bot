<?php

namespace App\Models;

use App\Casts\AutoTimezone;
use Bond\Ryo\Support\Timestamp;
use Bond\Ryo\Trace\TraceEloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * 模型基类
 *
 * @author beta
 */
class Model extends EloquentModel
{
    
    /**
     * 表名称
     *
     * @var string
     */
    protected $table = '';
    
    /**
     * 与表相关联的主键
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * 指示模型的ID不是自增的
     *
     * @var bool
     */
    public $incrementing = false;
    
    /**
     * 指示模型是否主动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;
    
    /**
     * 可以大量分配的属性
     *
     * @var array<string>
     */
    protected $fillable = [];
    
    /**
     * 应该为序列化而隐藏的属性
     *
     * @var array<int, string>
     */
    protected $hidden = [];
    
    /**
     * 应该强制转换的属性
     *
     * @var array
     */
    protected $casts = [];
    
    /**
     * 追踪类
     *
     * @var TraceEloquent
     */
    protected TraceEloquent $trace;
    
    /**
     * 创建一个新的 [Eloquent] 模型实例
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $trace = $this->trace;
        
        $this->fillable = array_values($trace::getAllColumns());
        $this->table = $trace::TABLE;
        $this->primaryKey = $trace::ID;
        
        $this->casts = array_merge($this->casts, [
            $trace::UPDATED_AT => AutoTimezone::class,
            $trace::CREATED_AT => AutoTimezone::class,
        ]);
        
        $this->hidden = $trace::HIDE;
        
        parent::__construct($attributes);
    }
    
    /**
     * 创建前执行的操作
     *
     * @param  Builder  $query
     * @return bool
     */
    protected function performInsert(Builder $query): bool
    {
        $trace = $this->trace;
        
        if (empty($this->getAttribute($trace::ID))) {
            $this->setAttribute($trace::ID, Timestamp::millisecond());
        }
        
        $this->setAttribute($trace::CREATED_AT, Timestamp::second());
        $this->setAttribute($trace::UPDATED_AT, Timestamp::second());
        
        return parent::performInsert($query);
    }
    
    /**
     * 执行模型更新操作
     *
     * @param  Builder  $query
     * @return bool
     */
    protected function performUpdate(Builder $query): bool
    {
        $trace = $this->trace;
        
        $this->setAttribute($trace::UPDATED_AT, Timestamp::second());
        
        return parent::performUpdate($query);
    }
    
}