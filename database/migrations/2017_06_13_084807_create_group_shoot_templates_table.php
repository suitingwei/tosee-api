<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGroupShootTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_shoot_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sort')->default(0);
            $table->string('title')->default('');
            $table->string('cover_url')->default('');
            $table->string('position')->default('');
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
        Schema::dropIfExists('group_shoot_templates');
    }
}
