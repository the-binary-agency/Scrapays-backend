<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePickupRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('producer_id');
            $table->string('assigned_collector')->nullable();
            $table->mediumText('address');
            $table->mediumText('producer_name');
            $table->longText('materials');
            $table->longText('comment')->nullable();
            $table->longText('description')->nullable();
            $table->longText('schedule');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('pickup_requests');
    }
}
