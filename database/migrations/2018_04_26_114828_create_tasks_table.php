<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('type');
            $table->jsonb('cards');
            $table->jsonb('attributes');
            /*
             attributes:
                 type:water
                    pay_status //支付状态 0：未支付 1：支付成功 -1:支付失败
                    status     //订单状态 0：未接受 1：已接受 2：申请完成 3：已完成 4：拒绝完成
                    apartment  //校四、校六
                    address    //房间
                    send       //是否送货上门
                    fee        //费用
                    acceptor   //接收者userId

              */
            $table->string('out_trade_no')->nullable();
            $table->string('pre_pay')->nullable();
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
