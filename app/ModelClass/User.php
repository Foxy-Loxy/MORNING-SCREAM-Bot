<?php


namespace App\ModelClass;

use App\Helpers\Localize;
use App\NewsCache;
use Carbon\Carbon;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Exceptions;


class User
{

    static public function scheduleCall(\App\User $user)
    {
        $locale = app(Localize::class);
        $catKeyboard = Keyboard::make([
            'keyboard' => [
                [$locale->getString("user_menu_ServicesKbd")],
                [$locale->getString("user_menu_ToggleDelivKbd")],
                [$locale->getString("user_menu_SetLangKbd")],
                [$locale->getString('cancel')]
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
        $locale = app(Localize::class);
        $servKeyboard = Keyboard::make([
            'keyboard' => [
                [$locale->getString("user_Services_NewsKbd")],
                [$locale->getString("user_Services_WeatherKbd")],
                [$locale->getString('cancel')]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $catKeyboard = Keyboard::make([
            'keyboard' => [
                [$locale->getString("user_menu_ServicesKbd")],
                [$locale->getString("user_menu_ToggleDelivKbd")],
                [$locale->getString("user_menu_SetLangKbd")],
                [$locale->getString('cancel')]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $calKeyboard = Keyboard::make(['❌ Cancel']);

        if ($user->function == \App\User::NAME && $user->function_state != null) {

            switch ($input) {

                case $locale->getString('cancel'):
                    $user->update([
                        'function' => null,
                        'function_state' => null
                    ]);
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => $locale->getString('canceled'),
                        'reply_markup' => $exitKbd
                    ]);
                    return false;
                    break;
            }
            switch ($user->function_state) {

                case 'WAITING_FOR_SETTING_CATEGORY':

                    switch ($input) {

                        case $locale->getString("user_menu_ServicesKbd"):
                            $user->update([
                                'function_state' => 'WAITING_FOR_SERVICES'
                            ]);
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => $locale->getString("user_Services_Enter"),
                                'reply_markup' => $servKeyboard
                            ]);
                            break;

                        case $locale->getString("user_Services_Enter"):
                            $user->update([
                                'delivery_enabled' => !$user->delivery_enabled
                            ]);
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => $locale->getString("user_ToggleDelivKbd_Body") . ($user->delivery_enabled ? $locale->getString("user_ToggleDelivKbd_Body_Enabled") : $locale->getString("user_ToggleDelivKbd_Body_Disabled")),
                                'reply_markup' => $catKeyboard
                            ]);
                            break;


                        case $locale->getString("user_menu_SetLangKbd"):
                            $user->update([
                                'function_state' => 'WAITING_FOR_LANGUAGE'
                            ]);
                            $langArr = $locale->getAllLocales();
                            $kbdArr = array();
                            foreach ($langArr as $lang)
                                $kbdArr[] = array(strtoupper($lang['short']) . ' | ' . $lang['full']);
                      		array_unshift($kbdArr, [$locale->getString('cancel'), "\u{2753} Can't find my language"]);
                            $Kbd = Keyboard::make([
                                'keyboard' => $kbdArr,
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true
                            ]);
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => $locale->getString("user_SetLang_Enter"),
                                'reply_markup' => $Kbd
                            ]);
                            break;

                        default:
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => $locale->getString("user_menu_Fail"),
                                'reply_markup' => $catKeyboard
                            ]);
                            break;
                    }

                    break;

                case 'WAITING_FOR_SERVICES':
                    $serArr = explode(',', ($user->services == null ? "" : $user->services));

                    switch ($input) {
                        case $locale->getString("user_Services_NewsKbd"):
                            if (in_array('news', $serArr))
                                unset($serArr[array_search('news', $serArr)]);
                            else
                                $serArr[] = 'news';
                            break;

                        case $locale->getString("user_Services_WeatherKbd"):
                            if (in_array('weather', $serArr))
                                unset($serArr[array_search('weather', $serArr)]);
                            else
                                $serArr[] = 'weather';
                            break;

                        default:
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => $locale->getString("user_Services_Fail"),
                                'reply_markup' => $servKeyboard
                            ]);
                            break;
                    }
                    $user->update([
                        'services' => implode(',', array_filter($serArr))
                    ]);
                    $list = '';

                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => $locale->getString("user_Services_List") . $user->FancyServices(),
                        'reply_markup' => $servKeyboard
                    ]);
                    break;

                case "WAITING_FOR_LANGUAGE":
                    $langArr = $locale->getAllLocales();
                    $kbdArr = array();
                    foreach ($langArr as $lang)
                        $kbdArr[] = array(strtoupper($lang['short']) . ' | ' . $lang['full']);
                    $langKbd = $kbdArr;
                    $exploded = explode(' | ', $input);
                    $short = strtolower($exploded[0]);
                    $long = (isset($exploded[1]) ? $exploded[1] : 'null');
                    $found = false;
                    foreach ($langArr as $lang){
                        if ($lang['short'] == $short && $short != $locale->current) {
                            $user->update([
                                'lang' => $short
                            ]);
                        $locale->setLocale($short);
                        array_unshift($kbdArr, [$locale->getString('cancel'), "\u{2753} Can't find my language"]);
                      		$Kbd = Keyboard::make([
                      		    'keyboard' => $kbdArr,
          						'resize_keyboard' => true,
          						'one_time_keyboard' => true
                  			]);
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => $locale->getString('user_SetLang_Success') . $long,
                            'reply_markup' => $Kbd
                        ]);
                        return true;
                        }
                    }
                    array_unshift($kbdArr, [$locale->getString('cancel'), "\u{2753} Can't find my language"]);
              		$Kbd = Keyboard::make([
	        		    'keyboard' => $kbdArr,
          				'resize_keyboard' => true,
          				'one_time_keyboard' => true
                  	]);
                    if ($input == "\u{2753} Can't find my language") {
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => "Unfortunately, developer of this bot don't know every single language of it's users. If you natively speak language, which isn't listed here, please, consider contributing to translation of this bot. Contact developer (@foxyloxy) for more information. Thank you !",
                            'reply_markup' => $Kbd
                        ]);
                        return true;
                    }
                    Telegram::sendMessage([
                  		'chat_id' => $user->chat_id,
                  		'text' => $locale->getString('user_SetLang_Fail'),
                  		'reply_markup' => $Kbd
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

