<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('mobile')->unique();
            $table->string('password');
            $table->string('avatar')->default("http://oz3rf0wt0.bkt.clouddn.com/18-1-22/15799237.jpg");
            $table->string('sex')->nullable();
            $table->string('role')->default('user');// 角色 默认user   admin  seller
            $table->string('wechat_openid')->nullable()->unique();
            $table->tinyInteger('status')->default(0); //0.未认证  1.已认证
            $table->string('id_pic_p')->nullable(); //身份证 正面
            $table->string('id_pic_n')->nullable();//身份证 反面
            $table->string('true_name')->nullable();
            $table->string('bank_card')->nullable();
            $table->string('bank_card_name')->nullable();
            $table->bigInteger('level')->default(0);
            $table->bigInteger('exp')->default(0);
            $table->integer('balance')->default(0); //余额
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
