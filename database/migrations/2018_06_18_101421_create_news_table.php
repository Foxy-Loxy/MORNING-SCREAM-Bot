<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewsTable extends Migration
{

    public function up()
    {
        Schema::create('news', function(Blueprint $table) {
            $table->increments('id');
            $table->string('chat_id');

            $table->foreign('chat_id')
                ->references('chat_id')
                ->on('users')
                ->onDelete('cascade');

            $table->string('categories')->nullable();
        });
    }

    public function down()
    {
        Schema::drop('news');
    }
}
