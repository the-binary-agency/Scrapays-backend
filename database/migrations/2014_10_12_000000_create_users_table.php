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
            $table->string('phone')->primary();
            $table->string('id', 6);
            $table->string('avatarImage');
            $table->string('firstName');
            $table->string('lastName');
            $table->string('role');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('address')->nullable();
            $table->string('companyName')->nullable();
            $table->string('companySize')->nullable();
            $table->string('industry')->nullable();
            $table->string('sex')->nullable();
            $table->string('requestAddress')->nullable();
            $table->string('hostAddress')->nullable();
            $table->string('hostDuration')->nullable();
            $table->string('spaceSize')->nullable();
            $table->string('hostStartDate')->nullable();
            $table->string('collectionCoverageZone')->nullable();
            $table->string('inviteCode')->nullable();
            $table->boolean('approvedAsCollector')->nullable();
            $table->boolean('recoveryAutomated')->nullable();
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
