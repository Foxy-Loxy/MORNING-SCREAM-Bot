<?php

namespace App\Console;

use App\ModelClass\News;
use App\ModelClass\Weather;
use App\NewsCache;
use App\User;
use App\WeatherCache;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            $timed = \App\Schedule::where('utc_time', Carbon::now()->setTimezone('UTC')->format('H:i'))->get();
            foreach ($timed as $time) {
          		app()->singleton(\App\Helpers\Localize::class, function () use ($time) {
          			return new \App\Helpers\Localize($time->user->lang);
          		});
                $serviceArr = explode(',', $time->user->services);
                try {
                    if (in_array('news', $serviceArr) && $time->user->delivery_enabled == true)
                        News::deliver($time->user);
                    if (in_array('weather', $serviceArr) && $time->user->delivery_enabled == true)
                        Weather::deliver($time->user);
                } catch (\Exception $e) {
                    $time->user->update([
                        'delivery_enabled' => false
                    ]);
                }
            }
        })->everyMinute();


           $schedule->call(function () {
          		WeatherCache::truncate();
          		NewsCache::truncate();
            }
        )->cron('0 0 * * *');

    }
}
