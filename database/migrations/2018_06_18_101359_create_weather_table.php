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

            $table->string('lon')->nullable()->default('0.1278');
            $table->string('lat')->nullable()->default('51.5074');
            $table->string('location')->nullable()->default('London,GB');
            $table->string('units')->default('metric');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('weather');
    }
}
