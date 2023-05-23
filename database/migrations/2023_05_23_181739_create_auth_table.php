<?php

use App\Models\Trace\AuthTrace as Trace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 授权数据表
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
