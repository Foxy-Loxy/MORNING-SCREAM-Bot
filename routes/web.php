<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

//Route for Telegram webhook update. All logic will go through here
$router->post('/webhook', 'WebhookController@trigger');
$router->get('/webhook', 'WebhookController@trigger');

//Testing purpose routes
$router->get('/', function () { echo 'Welcome to MORNING sCREAM root page';});
$router->get('/getMe', 'TestControllersController@getMe');
$router->get('/lastResp', 'TestControllersController@getLastResponse');
$router->get('/msgMe', 'TestControllersController@sendMessageToMe');
$router->get('/setwh', function () {
                try{
                    Telegram::setWebhook([
                        'url' => 'https://my-sandbox.strangled.net/morning-scream/webhook',
                        'certificate' => '/etc/ssl/certs/@cert.pem'
                    ]);
                } catch(TelegramResponseException $e) {
                    Telegram::sendMessage([
                        'chat_id' => '189423549',
                        'text' => 'Cron job failed. Response:' . $e->getResponse()
                    ]);
                }
		    });

//Route for generating app key
$router->get('/key', function() {
    return str_random(32);
});



/**
 * Routes for resource test-controller
 */

/**
 * Routes for resource webhook
 */
