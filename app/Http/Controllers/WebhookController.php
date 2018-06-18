<?php namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\JsonResponse;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Http\Request;

class WebhookController extends Controller {

    const MODEL = "App\Webhook";

    public function trigger(Request $request)
    {

        //Get all request data
        $rqData = $request->all();
        //Try to identifiy user
        $user = User::where('chat_id', $request->all()['message']['chat']['id'])->get();
        //Register user if not identified
        if ($user->isEmpty())
            User::create([
                'first_name' => $rqData['message']['chat']['first_name'],
                'last_name' => $rqData['message']['chat']['last_name'],
                'username' => (isset($rqData['message']['chat']['username']) ? $rqData['message']['chat']['username'] : null),
                'chat_id' => $rqData['message']['chat']['id'],
                'services' => null
            ]);
        //Determine command
        if (isset($rqData['message']['entities'][0]['type']) && $rqData['message']['entities'][0]['type'] == 'bot_command') {
            $command = substr($rqData['message']['text'], $rqData['message']['entities'][0]['offset'], $rqData['message']['entities'][0]['length']);
            $args = substr($rqData['message']['text'], $rqData['message']['entities'][0]['length'], strlen($rqData['message']['text']));
            switch ($command) {
                case '/help':

                    break;
                case '/setnewscat':

                    break;
                case '/setschedtime':

                    break;
            }

        } else
            Telegram::sendMessage([
                'chat_id' => $rqData['message']['chat']['id'],
                'text' => 'Hey, I\'m not a chatbot. Use commands listed in /help' ,
                'parse_mode' => 'html'
            ]);





        if (env('DEBUG_DUMP'))
            Telegram::sendMessage([
                'chat_id' => $request->all()['message']['chat']['id'],
                'text' => 'Yay ! Webhook reached ! ' . ($user->isNotEmpty() ? 'Hey, I know you !' : 'Welcome, I\'ll remember you. ') . ' You\'re ' . $request->all()['message']['chat']['first_name'] . ' ' . $request->all()['message']['chat']['last_name'] . ', a.k.a @' . (isset($request->all()['message']['chat']['username']) ? $request->all()['message']['chat']['username'] : 'null') . ' . Your chat id for this bot is: ' . $request->all()['message']['chat']['id'] . "\n JSON Request was: \n <code>" . json_encode($request->all(), JSON_PRETTY_PRINT) . '</code>',
                'parse_mode' => 'html'
            ]);



        return new JsonResponse('OK', 200);

        }

}
