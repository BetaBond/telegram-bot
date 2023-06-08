<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * 基础记账机器人数据导出
 *
 * @author southwan
 */
class BaseBillExport implements FromCollection
{
    
    /**
     * 导出数据
     *
     * @var Collection
     */
    protected Collection $data;
    
    /**
     * 构造导出
     *
     * @param  Collection  $data
     */
    public function __construct(Collection $data)
    {
        $this->data = $data;
    }
    
    /**
     * 导出集合
     *
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->data;
    }
    
}