<?php namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

class TestControllersController extends Controller {

    const MODEL = "App\TestController";

    public function getMe(){
        try {
            $response = Telegram::getMe();
        } catch (TelegramResponseException $e) {
            return new JsonResponse([$e->getResponse(), $e->getMessage(), config('bot_token')], 200);
        }
        return new JsonResponse($response, 200);
//        $this->validate($request, [ 'msg' => 'required',
//            'chat_id' => 'required']);
    }

    public function getLastResponse(){
        try {
            $response = Telegram::getUpdates();
        } catch (TelegramResponseException $e) {
            return new JsonResponse([$e->getResponse(), $e->getMessage(), config('bot_token')], 200);
        }
        return new JsonResponse($response, 200);
    }

    public function  sendMessageToMe(Request $request){
        $this->validate($request, [
           'msg' => 'required'
        ]);

        $q = new Keyboard(['1', '2']);



        Telegram::sendMessage([
            'chat_id' => '189423549',
            'text' => $request->input('msg'),
            'reply_markup' => Keyboard::make([
                'keyboard' => [['🗠 Business', '🎶 Entertainment', '🏥 Health'],
                    ['🔬 Science', '⚽ Sports', '🖥 Technology'],
                    ['📰 General']],
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ])
        ]);
    }

}
