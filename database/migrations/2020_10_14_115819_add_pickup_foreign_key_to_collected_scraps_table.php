<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPickupForeignKeyToCollectedScrapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collected_scraps', function (Blueprint $table) {

            $table->foreign('pickup_id')->references('id')->on('pickup_requests');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collected_scraps', function (Blueprint $table) {
            //
        });
    }
}
