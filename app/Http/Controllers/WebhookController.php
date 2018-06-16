<?php namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Http\Request;

class WebhookController extends Controller {

    const MODEL = "App\Webhook";

    public function trigger(Request $request) {

	Telegram::sendMessage([
	    'chat_id' => $request->all()['message']['chat']['id'],
	    'text' => 'Yay ! Webhook reached ! You\'re '. $request->all()['message']['chat']['first_name'] . ' ' . $request->all()['message']['chat']['last_name'] .', a.k.a @' . $request->all()['message']['chat']['username']  .' . Your chat id for this bot is: ' . $request->all()['message']['chat']['id'] 
]);
	return new JsonResponse('OK', 200);

    }

}
