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

$router->get('/getMe', 'TestControllersController@getMe');
$router->get('/lastResp', 'TestControllersController@getLastResponse');
$router->get('/msgMe', 'TestControllersController@sendMessageToMe');

$router->get('/key', function() {
    return str_random(32);
});
/**
 * Routes for resource test-controller
 */
