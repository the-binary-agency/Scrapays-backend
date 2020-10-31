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
            $table->string('producer_id');
            $table->string('producer_type');
            $table->string('collector_id');
            $table->string('pickup_id')->nullable();
            $table->longText('address');
            $table->longText('materials');
            $table->string('cost');
            $table->string('total_tonnage');
            $table->string('payment_method');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('collector_id')->references('id')->on('users');
            $table->foreign('producer_id')->references('id')->on('users');
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
