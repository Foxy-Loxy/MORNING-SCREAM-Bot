<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewsCacheTable extends Migration
{

    public function up()
    {
        Schema::create('news_cache', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->string('country')->nullable();
            $table->json('content');

            $table->timestamps();
            // Constraints declaration

        });
        \Illuminate\Support\Facades\DB::connection()->getpdo()->exec(
            'CREATE EVENT IF NOT EXISTS
                    ClearNewsCache
                    ON SCHEDULE EVERY 1 HOUR
                    DO
                    
                        DELETE FROM
                            news_cache
                                WHERE time_created < NOW() - INTERVAL 1 DAY
                    '
        );
    }

    public function down()
    {
        \Illuminate\Support\Facades\DB::connection()->getpdo()->exec('DROP EVENT IF EXISTS ClearNewsCache');
        Schema::drop('news_cache');
    }
}
