<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMergeShootsTitleToGroupShootsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_shoots', function (Blueprint $table) {
            $table->string('merge_shoots_title');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('group_shoots', function (Blueprint $table) {
            $table->dropColumn('merge_shoots_title');
        });
    }

