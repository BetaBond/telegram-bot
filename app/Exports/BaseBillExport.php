<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;

/**
 * 基础记账机器人数据导出
 *
 * @author southwan
 */
class BaseBillExport implements FromArray
{
    
    use Exportable;
    
    protected array $data;
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    public function array(): array
    {
        return $this->data;
    }
    
}