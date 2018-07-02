<?php namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Helpers\Localize;
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
		
//		return new JsonResponse($request->all(), 200);
		
//		Telegram::sendMessage([
//			'chat_id' => '189423549',
//			'text' => print_r($request->all(), true)
//		]);

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
        if ($user->isEmpty()){
            $user = User::create([
                'first_name' => $user_data['first_name'],
                'last_name' => $user_data['last_name'],
                'username' => $user_data['username'],
                'chat_id' => $user_data['chat_id'],
                'services' => 'news,weather',
                'function' => null,
                'function_args' => null,
            ]);
            Helper::createUserDefault($user->chat_id);
          }
        else
            $user = $user[0];

        $menuKeyboard = Keyboard::make([
            'keyboard' => [
                [Localize::getStringByLocale($user->lang, "main_newsKbd")],
                [Localize::getStringByLocale($user->lang, "main_weatherKbd")],
                [Localize::getStringByLocale($user->lang, "main_scheduleKbd")],
                [Localize::getStringByLocale($user->lang, "main_summaryKbd"), Localize::getStringByLocale($user->lang, "main_settingsKbd")]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

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
                        if ($user->function == 'callback' && $user->function_state == 'WAITING_TO_COMPLETE')
                            return new JsonResponse('OK', 200);
                switch ($input[0]) {
                    case 'article':
                        $user->update([
                            'function' => 'callback',
                            'function_state' => 'WAITING_TO_COMPLETE'
                        ]);
                        \App\ModelClass\News::scrollMessage($user, $input[1], $data['message']['message_id'], $data['id'], $input[2]);
                        return new JsonResponse('OK', 200);
                        break;
                        
                    case 'weather':
                        $user->update([
                            'function' => 'callback',
                            'function_state' => 'WAITING_TO_COMPLETE'
                        ]);
                        \App\ModelClass\Weather::scrollMessage($user, $data['message']['message_id'], $data['id'], $input[1]);
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
				try {
                $input = (isset($data['text']) ? $data['text'] : $data['location']);
				} catch (\Exception $e) {
					Telegram::sendMessage([
              			'chat_id' => $user->chat_id,
              			'text' => Localize::getStringByLocale($user->lang, "main_wrongInput")
              		]);
					return new JsonResponse('{ message:"ok" }', 200);
				}
                //
                // Determine whether any service is waiting for user action
                //
                if ($user->function_state != null)
                    switch ($user->function) {
                        case Weather::NAME:
                            \App\ModelClass\Weather::scheduleConfirm($user, $input, $menuKeyboard);
                            break;
                        case News::NAME:
                            \App\ModelClass\News::scheduleConfirm($user, $input, $menuKeyboard);
                            break;
                        case Schedule::NAME:
                            \App\ModelClass\Scheduler::scheduleConfirm($user, $input, $menuKeyboard);
                            break;
                        case User::NAME:
                            \App\ModelClass\User::scheduleConfirm($user, $input, $menuKeyboard);
                            break;
                        default:
                            $user->update(['function_state' => null, 'function' => null]);
                            break;
                    }
                else
                    switch ($input) {
                        case Localize::getStringByLocale($user->lang, "main_newsKbd"):
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => Localize::getStringByLocale($user->lang, "main_newsCommand")
                            ]);
                            \App\ModelClass\News::scheduleCall($user);
                            break;

                        case Localize::getStringByLocale($user->lang, "main_weatherKbd"):
                      		\App\ModelClass\Weather::scheduleCall($user);
                            break;

                        case Localize::getStringByLocale($user->lang, "main_scheduleKbd"):

                            Telegram::sendMessage([
                                'chat_id' => $rqData['message']['chat']['id'],
                                'text' => Localize::getStringByLocale($user->lang, "main_scheduleCommand"),
                            ]);
                            \App\ModelClass\Scheduler::scheduleCall($user);
                            break;

                        case "Force News":
                            \App\ModelClass\News::deliver($user);
                            break;
                        case "Force Weather":
                            \App\ModelClass\Weather::deliver($user);
                            break;
                        case Localize::getStringByLocale($user->lang, "main_summaryKbd"):
                            Telegram::sendMessage([
                                'chat_id' => $rqData['message']['chat']['id'],
                                'text' => Localize::getStringByLocale($user->lang, 'summary_head') . "\n===============\n". Localize::getStringByLocale($user->lang, 'summary_newsCat') . ($user->news != null ? $user->news->categories : Localize::getStringByLocale($user->lang, 'none'))
                                    . "\n===============\n" . Localize::getStringByLocale($user->lang, 'summary_delivTime') . ($user->schedule != null ? $user->schedule->time . ' ' . $user->schedule->utc . ' UTC' : Localize::getStringByLocale($user->lang, 'none')) . ' (' . ($user->schedule->utc_time != null ? $user->schedule->utc_time : Localize::getStringByLocale($user->lang, 'none')) . ' UTC)',
                                'parse_mode' => 'html',
                                'reply_markup' => Keyboard::make()
                                    ->inline()
                                    ->row(
                                        Keyboard::inlineButton(['text' => 'Credits', 'callback_data' => 'credits']),
                                        Keyboard::inlineButton(['text' => 'Donate', 'callback_data' => 'donate'])
                                    )
                            ]);
                            break;

                        case Localize::getStringByLocale($user->lang, "main_settingsKbd"):
                            \App\ModelClass\User::scheduleCall($user);
                            Telegram::sendMessage([
                                'chat_id' => $rqData['message']['chat']['id'],
                                'text' => Localize::getStringByLocale($user->lang, "main_settingsCommand"),
                            ]);
                            break;

                        default:
                            Telegram::sendMessage([
                                'chat_id' => $rqData['message']['chat']['id'],
                                'text' => Localize::getStringByLocale($user->lang, "main_noCommand"),
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
