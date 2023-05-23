<?php

use App\Models\Trace\RobotsTrace as Trace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 机器人信息表
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
            $table->bigInteger(Trace::ID)->primary()->unique()->comment('机器人ID');
            $table->bigInteger(Trace::T_UID)->comment('Telegram UID');
            $table->string(Trace::USERNAME, 64)->comment('Telegram 用户名');
            $table->string(Trace::TOKEN, 64)->comment('机器人凭证');
            $table->decimal(Trace::INCOMING_RATE, 9, 4)->comment('入账汇率');
            $table->decimal(Trace::PAYMENT_EXCHANGE_RATE, 9, 4)->comment('出账汇率');
            $table->decimal(Trace::RATING, 9, 4)->comment('费率');
            $table->integer(Trace::CREATED_AT)->comment('创建时间');
            $table->integer(Trace::UPDATED_AT)->comment('修改时间');
            $table->integer(Trace::EXPIRE_AT)->comment('到期时间');
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
