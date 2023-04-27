<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 账单数据表
 *
 * @author southwan
 */
return new class extends Migration {
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('bill', function (Blueprint $table) {
            $table->id()->comment('账单ID');
            $table->tinyInteger('type')->comment('账目类型 (-1:出账 / 1:入账)');
            $table->integer('t_uid')->comment('Telegram UID');
            $table->decimal('decimal')->comment('金额');
            $table->decimal('exchange_rate')->comment('汇率');
            $table->integer('created_at')->comment('创建时间');
            $table->integer('updated_at')->comment('修改时间');
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('bill');
    }
    
};
