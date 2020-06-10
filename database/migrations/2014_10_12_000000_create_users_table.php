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
            $table->string('firstName');
            $table->string('lastName');
            $table->string('role');
            $table->string('type');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('address');
            $table->string('collectionCoverageZone');
            $table->string('specificLocationAddress');
            $table->string('RCNo');
            $table->string('TIN');
            $table->boolean('approvedAsCollector');
            $table->boolean('recoveryAutomated');
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
