<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupShootRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_shoot_rules', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('groupshoot_id');

            $table->string('theme');        //主题

            $table->integer('time');                    //时长,10s/15s
            $table->string('canvas_direction');        //画幅方向
            $table->string('camera_direction');        //镜头方向

            $table->integer('enable_red_bag');             //是否有红包
            $table->integer('enable_camera_filter');    //是否允许滤镜
            $table->integer('enable_music');            //是否允许配乐
            $table->integer('enable_sticker');          //是否允许贴纸

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
        Schema::dropIfExists('group_shoot_rules');
    }
}
