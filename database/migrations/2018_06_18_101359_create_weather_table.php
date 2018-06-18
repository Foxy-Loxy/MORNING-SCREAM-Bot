<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWeatherTable extends Migration
{

    public function up()
    {
        Schema::create('weather', function(Blueprint $table) {
            $table->increments('id');
            $table->string('chat_id');

            $table->foreign('chat_id')
                ->references('chat_id')
                ->on('users')
                ->onDelete('cascade');

            $table->string('lon');
            $table->string('lat');
            $table->string('location');
        });
    }

    public function down()
    {
        Schema::drop('weather');
    }
}
