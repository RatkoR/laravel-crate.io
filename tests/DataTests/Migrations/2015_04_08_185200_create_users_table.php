<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('t_users')) return;
        Schema::create('t_users', function(Blueprint $table)
        {
            $table->integer('id');
            $table->string('name');
            $table->string('email');
            $table->string('password', 60);
            $table->arrayField('f_array');
            $table->objectField('f_object');

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
        Schema::drop('t_users');
    }

}
