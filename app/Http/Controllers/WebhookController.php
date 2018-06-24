<?php namespace App\Http\Controllers;

use App\Helpers\Helper;
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

        //
        // Listen to every exception and report it to developer
        //
        /*
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
        */

//        try {

            //
            // Prepare keyboard to be supplied
            //
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

            //
            // Get all request data
            //
            if ($request->has('json'))
                $rqData = json_decode($request->input('json'), true);
            else
                $rqData = $request->all();

            $user_data = Helper::getUserData($rqData);

            //
            //Try to identifiy user
            //
            $user = User::where('chat_id', $user_data['chat_id'])->get();

            //
            // Register user if not identified
            //
            if ($user->isEmpty())
                $user = User::create([
                    'first_name' => $user_data['first_name'],
                    'last_name' => $user_data['last_name'],
                    'username' => $user_data['username'],
                    'chat_id' => $user_data['chat_id'],
                    'services' => null,
                    'function' => null,
                    'function_args' => null
                ]);
            else
                $user = $user[0];

            //
            // Determine data type given and perfom action
            //

            $data = Helper::getInputData($rqData);

            switch ($data['type']) {
                case 'callback_query':
					$data = $data['data'];
                    //
                    // Determine callback query action and perform it
                    //
                    $input = $rqData['callback_query']['data'];
                    $input = explode(' ', $input);

                    switch ($input[0]) {
                        case 'article':

                            if ($user->function == 'callback' && $user->function_state == 'WAITING_TO_COMPLETE')
                                return new JsonResponse('OK', 200);
                            $user->update([
                               'function' => 'callback',
                               'function_state' => 'WAITING_TO_COMPLETE'
                            ]);
                            \App\ModelClass\News::scrollMessage($user, $input[1], $data['message']['message_id'], $data['id'] , $input[2]);
                            return new JsonResponse('OK', 200);

                            break;
                        case 'null':
                        
                      		Telegram::answerCallbackQuery([
                      			'callback_query_id' => $data['id']
                      		]);
                            return new JsonResponse('OK', 200);

                            break;
                            default:
                        		Telegram::answerCallbackQuery([
                      			'callback_query_id' => $data['id']
                      		]);
                            return new JsonResponse('OK', 200);                            
                            break;
                    }

                    break;

                case  'message':
					$data = $data['data'];
                    $input = $data['text'];

                    //
                    // Determine whether any service is waiting for user action
                    //
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
                                $user->update(['function_state' => null, 'function' => null]);
                                break;
                        }
                        else
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
                    break;
            }
/*
        } catch (\Exception $e) {

            if (env('DEBUG_DUMP')) {
                Telegram::sendMessage([
                    'chat_id' => '189423549',
                    'text' => 'Exception: <strong>' . $e->getMessage() . '</strong> at line <strong>' . $e->getLine() . '</strong> in <em>' . $e->getFile() . "</em>\n"
                        . "Input was:\n <code>" . json_encode($request->all(), JSON_PRETTY_PRINT) . "</code>",
                    'parse_mode' => 'html',
                    'reply_markup' => $menuKeyboard
                ]);
                return new JsonResponse('OK', 200);
            }


        }
*/
        return new JsonResponse('OK', 200);

    }

}
