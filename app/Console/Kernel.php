<?php

namespace App\Console;

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
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call( function () {
            try{
                Telegram::setWebhook([
                    'url' => 'https://my-sandbox.strangled.net/morning-scream/webhook',
                    'certificate' => '/etc/ssl/certs/cert.pem'
                ]);
            } catch(TelegramResponseException $e) {
                Telegram::sendMessage([
                    'chat_id' => '189423549',
                    'text' => 'Cron job failed. Response:' . $e->getResponse()
                ]);
            }
        })->twiceDaily(1, 13);
    }
}
