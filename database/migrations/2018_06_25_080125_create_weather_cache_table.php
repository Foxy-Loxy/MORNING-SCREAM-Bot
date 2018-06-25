<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWeatherCacheTable extends Migration
{

    public function up()
    {
        Schema::create('Weather_Cache', function(Blueprint $table) {
            $table->string('location')->unique();
            $table->string('units')->default('metric');
            $table->json('content');
            $table->timestamps();
            // Schema declaration
            // Constraints declaration
        });
        \Illuminate\Support\Facades\DB::connection()->getpdo()->exec(
            'CREATE EVENT IF NOT EXISTS
                    ClearWeatherCache
                    ON SCHEDULE EVERY 1 HOUR
                    DO
                    
                        DELETE FROM
                            weather_cache
                                WHERE time_created < NOW() - INTERVAL 1 DAY
                    '
        );
    }

    public function down()
    {
        \Illuminate\Support\Facades\DB::connection()->getpdo()->exec('DROP EVENT IF EXISTS ClearWeatherCache');
        Schema::drop('Weather_Cache');
    }
}
