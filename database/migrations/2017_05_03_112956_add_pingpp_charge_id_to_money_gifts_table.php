<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPingppChargeIdToMoneyGiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('money_gifts', function (Blueprint $table) {
            $table->string('pingpp_charge_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('money_gifts', function (Blueprint $table) {
            $table->dropColumn('pingpp_charge_id');
        });
    }
}
