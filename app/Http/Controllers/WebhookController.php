<?php namespace App\Http\Controllers;

use App\News;
use App\Schedule;
use App\User;
use App\Weather;
use Illuminate\Http\JsonResponse;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Keyboard\Keyboard;
use Illuminate\Http\Request;

class WebhookController extends Controller
{

    const MODEL = "App\Webhook";

    public function trigger(Request $request)
    {

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        });

        try {
            $menuKeyboard = Keyboard::make([
                'keyboard' => [
                    ["\u{1F4F0} Set news categories"],
                    ["\u{1F321} Set weather preferences"],
                    ["\u{23F0} Set daily delivery time"],
                    ["\u{1F527} See all account preferences"]
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ]);
            //Get all request data
            $rqData = $request->all();
            $user = new User();
            if ($request->has('json')) {
                $rqData = json_decode($request->input('json'), true);
                $user = User::where('chat_id', (isset($rqData['message']) ? $rqData['message']['chat']['id'] : $rqData['callback_query']['from'] ))->get();
            } else
                $user = User::where('chat_id', (isset($rqData['message']) ? $rqData['message']['chat']['id'] : $rqData['callback_query']['from'] ))->get();
            //dd($rqData, $user);
            //Try to identifiy user


            if (!env('DEBUG_DUMP'))
                Telegram::sendMessage([
                    'chat_id' => $request->all()['message']['chat']['id'],
                    'text' => 'Yay ! Webhook reached ! ' . ($user->isNotEmpty() ? 'Hey, I know you !' : 'Welcome, I\'ll remember you. ') . ' You\'re ' . $request->all()['message']['chat']['first_name'] . ' ' . $request->all()['message']['chat']['last_name'] . ', a.k.a @' . (isset($request->all()['message']['chat']['username']) ? $request->all()['message']['chat']['username'] : 'null') . ' . Your chat id for this bot is: ' . $request->all()['message']['chat']['id'] . "\n JSON Request was: \n <code>" . json_encode($request->all(), JSON_PRETTY_PRINT) . '</code>',
                    'parse_mode' => 'html'
                ]);

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
            

            if (isset($user[0]))
                $user = $user[0];

            if (!isset($rqData['message']) && isset($rqData['callback_query'])){
                $input = $rqData['callback_query']['data'];
                $input = explode(' ', $input);

                switch ($input[0]){
                    case 'article':
//                        dd('article reached', $input);
                        \App\ModelClass\News::deliver($user,$input[1],$rqData['callback_query']['message']['message_id'], $input[2]);
                        return true;
                        break;
                }
            } else
				$input = $rqData['message']['text'];
            //If some service waiting for argument - skip command check
            if ($user->function_state != null)
                switch ($user->function) {
                    case Weather::NAME:
                        \App\ModelClass\Weather::scheduleConfirm($user, $input);
                        break;
                    case News::NAME:
                        \App\ModelClass\News::scheduleConfirm($user, $input, $menuKeyboard);
                        break;
                    case Schedule::NAME:
                        \App\ModelClass\Scheduler::scheduleConfirm($user, $input, $menuKeyboard);
                        break;
                    default:
                        $user->update([
                            'function' => null,
                            'function_args' => null
                        ]);
                        break;
                }
            else {
                switch ($input) {
                    case "\u{1F4F0} Set news categories":
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => 'News'
                        ]);
                        \App\ModelClass\News::scheduleCall($user);
                        break;

                    case "\u{1F321} Set weather preferences":
                        Telegram::sendMessage([
                            'chat_id' => $rqData['message']['chat']['id'],
                            'text' => 'Not implemented for now',
                            'parse_mode' => 'html',
                            'reply_markup' => $menuKeyboard
                        ]);
                        break;

                    case "\u{23F0} Set daily delivery time":

                        Telegram::sendMessage([
                            'chat_id' => $rqData['message']['chat']['id'],
                            'text' => 'Scheduling',
                        ]);
                        \App\ModelClass\Scheduler::scheduleCall($user);
                        break;

                    case "Force News":
                        \App\ModelClass\News::deliver($user);
                        break;
                    case "\u{1F527} See all account preferences":
                        Telegram::sendMessage([
                            'chat_id' => $rqData['message']['chat']['id'],
                            'text' => "ACCOUNT SUMMARY 	\n===============\nNews categories you've subscribed for: " . ($user->news != null ? $user->news->categories : 'None')
                                . "\n===============\nDelivery time: " . ($user->schedule != null ? $user->schedule->time . ' ' . $user->schedule->utc . ' UTC' : 'None') . ' (' . ($user->schedule->utc_time != null ? $user->schedule->utc_time : 'None') . ' UTC)',
                            'parse_mode' => 'html',
                            'reply_markup' => Keyboard::make()
                                ->inline()
                                ->row(
                                    Keyboard::inlineButton(['text' => 'Test', 'callback_data' => 'data']),
                                    Keyboard::inlineButton(['text' => 'Btn 2', 'callback_data' => 'data_from_btn2'])
                                )
                        ]);
                        break;

                    default:
                        Telegram::sendMessage([
                            'chat_id' => $rqData['message']['chat']['id'],
                            'text' => 'Hey, I\'m not a chatbot. Use commands listed below',
                            'parse_mode' => 'html',
                            'reply_markup' => $menuKeyboard
                        ]);
                        break;
                }
            }


//		dd($input, "\u{2699} See all account preferences", "\xe2\x9a\x99", $input == "\u{2699} See all account preferences");


            return new JsonResponse('OK', 200);
        } catch (\Exception $e) {
        
        /*
            Telegram::sendMessage([
                'chat_id' => '189423549',
                'text' => 'Exception: <strong>' . $e->getMessage() . '</strong> at line <strong>' . $e->getLine() . '</strong> in <em>' . $e->getFile() . "</em>\n"
                    . "Input was:\n <code>" . json_encode($request->all(), JSON_PRETTY_PRINT) . "</code>",
                'parse_mode' => 'html',
                'reply_markup' => $menuKeyboard
            ]);
            dd($e->getMessage());
        */

        }
    }

}
