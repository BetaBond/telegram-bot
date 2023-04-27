<?php

use App\Models\Trace\BillTrace as Trace;
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
        Schema::create(Trace::TABLE, function (Blueprint $table) {
            $table->bigInteger(Trace::ID)->primary()->unique()->comment('账单ID');
            $table->tinyInteger(Trace::TYPE)->comment('账目类型 (-1:出账 / 1:入账)');
            $table->bigInteger(Trace::T_UID)->comment('Telegram UID');
            $table->decimal(Trace::MONEY)->comment('金额');
            $table->decimal(Trace::EXCHANGE_RATE)->comment('汇率');
            $table->integer(Trace::CREATED_AT)->comment('创建时间');
            $table->integer(Trace::UPDATED_AT)->comment('修改时间');
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(Trace::TABLE);
    }
    
};
