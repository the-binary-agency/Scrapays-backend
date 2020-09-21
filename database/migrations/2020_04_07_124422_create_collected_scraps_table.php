<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectedScrapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collected_scraps', function (Blueprint $table) {
            $table->string('id', 6)->primary();
            $table->string('producerPhone'); 
            $table->string('producerType'); 
            $table->string('collectorID'); 
            $table->string('pickupID')->nullable(); 
            $table->longText('address'); 
            $table->longText('materials'); 
            $table->string('cost'); 
            $table->string('totalTonnage'); 
            $table->string('paymentMethod'); 
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
        Schema::dropIfExists('collected_scraps');
    }
}
