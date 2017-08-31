<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttemptTimesToMoneyTransferTable extends Migration
{
    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::table('money_transfers', function (Blueprint $table) {
            $table->integer('attempt_times')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::table('money_transfers', function (Blueprint $table) {
            $table->dropColumn('attempt_times');
        });
    }
}
