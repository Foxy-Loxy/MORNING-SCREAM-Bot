<?php


namespace App\ModelClass;

use App\NewsCache;
use Carbon\Carbon;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Exceptions;


class User
{

    static public function scheduleCall(\App\User $user)
    {
        $catKeyboard = Keyboard::make([
            'keyboard' => [
                ["\u{1F4EE} Set services enabled to deliver"],
                ["\u{1F579} Toggle delivering status"],
                ['❌ Cancel']
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
        //Since only operation available for this service will be
        $user->update([
            'function' => \App\User::NAME,
            'function_state' => 'WAITING_FOR_SETTING_CATEGORY'
        ]);
        Telegram::sendMessage([
            'chat_id' => $user->chat_id,
            'text' => 'Your settings',
            'reply_markup' => $catKeyboard
        ]);
    }

    static public function scheduleConfirm(\App\User $user, string $input, Keyboard $exitKbd)
    {
        $servKeyboard = Keyboard::make([
            'keyboard' => [
                ["\u{1F4F0} News"],
                ["\u{2600} Weather"],
                ['❌ Cancel']
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $catKeyboard = Keyboard::make([
            'keyboard' => [
                ["\u{1F4EE} Set services enabled to deliver"],
                ["\u{1F579} Toggle delivering status"],
                ['❌ Cancel']
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        if ($user->function == \App\News::NAME && $user->function_state != null) {

            switch ($input) {

                case "\u{274C} Cancel":
                    $user->update([
                        'function' => null,
                        'function_state' => null
                    ]);
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => 'Canceled',
                        'reply_markup' => $exitKbd
                    ]);
                    return false;
                    break;
            }
            switch ($user->function_state) {

                case 'WAITING_FOR_SETTING_CATEGORY':

                    switch ($input) {

                        case "\u{1F4EE} Set services enabled to deliver":
                            $user->update([
                                'function_status' => 'WAITING_FOR_SERVICES'
                            ]);
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => 'Select services listed below to toggle them for delivering',
                                'reply_markup' => $servKeyboard
                            ]);
                            break;

                        case "\u{1F579} Toggle delivering status":
                            $user->update([
                                'delivery_enabled' => !$user->delivery_enabled
                            ]);

                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => 'Delivery now is ' . ($user->delivery_enabled ? 'enabled' : 'disabled'),
                                'reply_markup' => $catKeyboard
                            ]);
                            break;

                        default:
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => 'Choose setting from list below',
                                'reply_markup' => $catKeyboard
                            ]);
                            break;
                    }

                    break;

                case "WAITING_FOR_SERVICES":
                    $serArr = explode(',', ($user->services == null ? "" : $user->services == null));
                    switch ($input) {
                        case "\u{1F4F0} News":
                            if (in_array('news', $serArr))
                                unset($serArr[array_search('news', $serArr)]);
                            else
                                $serArr[] = 'news';
                            break;

                        case "\u{2600} Weather":
                            if (in_array('weather', $serArr))
                                unset($serArr[array_search('weather', $serArr)]);
                            else
                                $serArr[] = 'weather';
                            break;

                        default:
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => 'Choose service from list below',
                                'reply_markup' => $servKeyboard
                            ]);
                            break;
                    }
                    $user->update([
                        'services' => implode(',', array_filter($serArr))
                    ]);
                    $list = '';
                    foreach ($serArr as $ser)
                        $list .= ucfirst($ser) . ' | ';
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => 'List of services enabled: ' . $list,
                        'reply_markup' => $servKeyboard
                    ]);
                    break;

            }
        }
        return true;
    }

    static public function deliver(\App\User $user)
    {

    }


    static public function scrollMessage(\App\User $user, int $article, int $messageId, int $callbackId, string $cat)
    {

    }

}

