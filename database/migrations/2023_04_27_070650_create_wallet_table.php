<?php

use App\Models\Trace\WalletTrace as Trace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 钱包数据表
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
            )->primary()->unique()->comment('钱包ID');

            $table->bigInteger(Trace::T_UID)->comment('所有者 ID');
            $table->string(Trace::NAME, 64)->comment('钱包名称');
            $table->decimal(Trace::BALANCE, 9, 4)->comment('钱包余额');
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
