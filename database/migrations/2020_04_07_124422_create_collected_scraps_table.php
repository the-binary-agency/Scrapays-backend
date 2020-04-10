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
            $table->string('producerID'); 
            $table->string('collectorID'); 
            $table->string('vendorID'); 
            $table->string('metal'); 
            $table->string('aluminium'); 
            $table->string('plastic'); 
            $table->string('paper'); 
            $table->string('others'); 
            $table->string('vendorApproved');
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
