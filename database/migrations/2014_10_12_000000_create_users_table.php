<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->string('id', 9);
            $table->string('firstName');
            $table->string('lastName');
            $table->string('phone')->primary();
            $table->string('email')->unique()->nullable();
            $table->string('avatarImage');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('inviteCode')->nullable();   
            $table->longText('api_token')->nullable();   
            $table->integer('userable_id')->nullable();  
            $table->string('userable_type')->nullable();   
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
