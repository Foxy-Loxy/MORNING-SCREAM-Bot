<?php namespace App\Http\Controllers;

use App\News;
use App\Schedule;
use App\User;
use App\Weather;
use Illuminate\Http\JsonResponse;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Http\Request;

class WebhookController extends Controller
{

    const MODEL = "App\Webhook";

    public function trigger(Request $request)
    {

        //Get all request data
        $rqData = $request->all();
        //Try to identifiy user
        $user = User::where('chat_id', $request->all()['message']['chat']['id'])->get();
        //Register user if not identified
        if ($user->isEmpty())
            $user = User::create([
                'first_name' => $rqData['message']['chat']['first_name'],
                'last_name' => $rqData['message']['chat']['last_name'],
                'username' => (isset($rqData['message']['chat']['username']) ? $rqData['message']['chat']['username'] : null),
                'chat_id' => $rqData['message']['chat']['id'],
                'services' => null,
                'function' => null,
                'function_args' => null
            ]);
        $input = $rqData['message']['text'];
        //If some service waiting for argument - skip command check
        if ($user->function_args != null)
            switch ($user->function) {
                case Weather::NAME:
                    \App\ModelClass\Weather::scheduleConfirm($user, $input);
                    break;
                case News::NAME:
                    \App\ModelClass\News::scheduleConfirm($user, $input);
                    break;
                case Schedule::NAME:
                    \App\ModelClass\Scheduler::scheduleConfirm($user, $input);
                    break;
                default:
                    $user->update([
                        'function' => null,
                        'function_args' => null
                    ]);
                    break;
            }
        else {
            //Determine command
            switch ($input) {
                case '\ud83d\udcf0 Set news category':

                    break;

                case '\ud83c\udf21 Set weather preferences':

                    break;

                case '⏰ Set daily delivery time':

                    break;

                default:
                    Telegram::sendMessage([
                        'chat_id' => $rqData['message']['chat']['id'],
                        'text' => 'Hey, I\'m not a chatbot. Use commands listed in /help',
                        'parse_mode' => 'html'
                    ]);
                    break;
            }
        }


        if (env('DEBUG_DUMP'))
            Telegram::sendMessage([
                'chat_id' => $request->all()['message']['chat']['id'],
                'text' => 'Yay ! Webhook reached ! ' . ($user->isNotEmpty() ? 'Hey, I know you !' : 'Welcome, I\'ll remember you. ') . ' You\'re ' . $request->all()['message']['chat']['first_name'] . ' ' . $request->all()['message']['chat']['last_name'] . ', a.k.a @' . (isset($request->all()['message']['chat']['username']) ? $request->all()['message']['chat']['username'] : 'null') . ' . Your chat id for this bot is: ' . $request->all()['message']['chat']['id'] . "\n JSON Request was: \n <code>" . json_encode($request->all(), JSON_PRETTY_PRINT) . '</code>',
                'parse_mode' => 'html'
            ]);


        return new JsonResponse('OK', 200);

    }

}
