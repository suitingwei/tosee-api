<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSampleFrameKeysToGroupShootsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_shoots', function (Blueprint $table) {
            $table->string('sample_frame_keys');
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
            $table->dropColumn('sample_frame_keys');
        });
    }
}
