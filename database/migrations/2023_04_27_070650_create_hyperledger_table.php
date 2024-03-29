<?php

use App\Models\Trace\HyperledgerTrace as Trace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 超级账本数据表
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
            $table->bigInteger(
                Trace::ID
            )->primary()->unique()->comment('账本ID');

            $table->tinyInteger(
                Trace::TYPE
            )->comment('账目类型 (-1:出账 / 1:入账)');

            $table->bigInteger(Trace::T_UID)->comment('Telegram UID');
            $table->bigInteger(Trace::ROBOT_ID)->comment('机器人 ID');
            $table->bigInteger(Trace::WALLET_ID)->comment('钱包ID');
            $table->string(Trace::USERNAME, 64)->comment('Telegram 用户名');
            $table->decimal(Trace::MONEY, 9, 4)->comment('金额');
            $table->decimal(Trace::EXCHANGE_RATE, 9, 4)->comment('汇率');
            $table->decimal(Trace::RATE, 9, 4)->comment('费率');
            $table->string(Trace::REMARK, 1024)->comment('备注');
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
