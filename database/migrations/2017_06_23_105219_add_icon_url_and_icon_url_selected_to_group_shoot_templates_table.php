<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIconUrlAndIconUrlSelectedToGroupShootTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_shoot_templates', function (Blueprint $table) {
            $table->string('icon_url')->default('');
            $table->string('icon_url_selected')->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('group_shoot_templates', function (Blueprint $table) {
            $table->dropColumn('icon_url');
            $table->dropColumn('icon_url_selected');
        });
    }
}
