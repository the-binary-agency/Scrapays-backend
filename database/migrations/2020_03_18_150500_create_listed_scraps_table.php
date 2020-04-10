<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListedScrapsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('listed_scraps', function (Blueprint $table) {
            $table->string('id', 6)->primary();
            $table->string('firstName'); 
            $table->string('lastName'); 
            $table->string('phone'); 
            $table->string('email'); 
            $table->string('materialImages'); 
            $table->text('materialLocation'); 
            $table->text('materialDescription');
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
        Schema::dropIfExists('listed_scraps');
    }
}
