<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCalendarTable extends Migration
{

    public function up()
    {
        Schema::create('calendar', function(Blueprint $table) {
            $table->string('chat_id');
            $table->json('data');

            $table->foreign('chat_id')
                ->references('chat_id')
                ->on('users')
                ->onDelete('cascade');

            $table->timestamps();
            // Schema declaration
            // Constraints declaration
        });
    }

    public function down()
    {
        Schema::drop('calendar');
    }
}
