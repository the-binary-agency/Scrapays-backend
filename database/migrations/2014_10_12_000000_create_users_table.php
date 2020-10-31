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
            $table->string('id', 9)->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('email')->unique()->nullable();
            $table->string('avatar_image');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->longText('pin');
            $table->string('invite_code')->nullable();
            $table->longText('api_token')->nullable();
            $table->integer('userable_id')->nullable();
            $table->longText('userable_type')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->string('total_earnings')->nullable();
            $table->string('total_tonnage')->nullable();
            $table->string('total_withdrawals')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
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

// $table->string('id', 9);
// $table->string('first_name');
// $table->string('last_name');
// $table->string('phone')->primary();
// $table->string('email')->unique()->nullable();
// $table->string('avatar_image');
// $table->timestamp('email_verified_at')->nullable();
// $table->string('password');
// $table->longText('pin');
// $table->string('invite_code')->nullable();
// $table->longText('api_token')->nullable();
// $table->timestamp('last_login')->nullable();
// $table->string('total_earnings')->nullable();
// $table->string('total_tonnage')->nullable();
// $table->string('total_withdrawals')->nullable();
// // enterprise-specific
// $table->string('company_name')->nullable();
// $table->string('company_size')->nullable();
// $table->string('industry')->nullable();
// $table->string('address')->nullable();
// $table->string('gender')->nullable();
// $table->boolean('recovery_automated')->nullable();
// $table->boolean('admin_automated')->nullable();
// // household-specific
// $table->string('request_address')->nullable();
// // collector-specific
// $table->string('collection_coverage_zone')->nullable();
// $table->boolean('approved_as_collector')->nullable();
// $table->longText('current_loc')->nullable();

// $table->rememberToken();
// $table->timestamps();
