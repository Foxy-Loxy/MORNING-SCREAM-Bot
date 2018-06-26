<?php


namespace App\ModelClass;

use App\Helpers\Helper;
use App\NewsCache;
use App\User;
use App\WeatherCache;
use Carbon\Carbon;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Exceptions;


class Weather
{

    static public function scheduleCall(User $user)
    {
        $setKeyboard = Keyboard::make([
            'keyboard' => [
                ["\u{1F321} Toggle between metric or imperial units"],
                ["\u{1F30D} Set location to get weather"],
                ['❌ Cancel']
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $tmp = \App\Weather::where('chat_id', $user->chat_id)->get();
        if ($tmp->isEmpty())
            \App\Weather::create([
                'chat_id' => $user->chat_id
            ]);

        $user->update([
            'function' => \App\Weather::NAME,
            'function_state' => 'WAITING_FOR_SETTING'
        ]);
        Telegram::sendMessage([
            'chat_id' => $user->chat_id,
            'text' => 'Choose a setting from listed on keyboard',
            'reply_markup' => $setKeyboard
        ]);
    }

    static public function scheduleConfirm(User $user, $input, Keyboard $exitKbd)
    {
        $setKeyboard = Keyboard::make([
            'keyboard' => [
                ["\u{1F321} Toggle between metric or imperial units"],
                ["\u{1F30D} Set location to get weather"],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $canKeyboard = Keyboard::make([
            'keyboard' => [
                ['❌ Cancel']
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        if ($user->function == \App\Weather::NAME && $user->function_state != null) {

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

                case "\u{1F321} Toggle between metric or imperial units":
                    $weather = \App\Weather::where('chat_id', $user->chat_id)->get()[0];
                    if ($weather->units == 'metric')
                        $weather->update([
                            'units' => 'imperial'
                        ]);
                    else
                        $weather->update([
                            'units' => 'metric'
                        ]);
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => 'Your prefered units now are set to ' . $weather->units,
                        'reply_markup' => $setKeyboard
                    ]);
                    break;

                case "\u{1F30D} Set location to get weather":
                    $user->update([
                        'function_state' => 'WAITING_FOR_LOCATION'
                    ]);
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => 'Send me your location to set weather origin',
                        'reply_markup' => $canKeyboard
                    ]);
                    break;
            }
            switch ($user->function_state) {

                case 'WAITING_FOR_LOCATION':
                    if (!isset($input['latitude'])) {
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => 'Send me your location to set weather origin',
                            'reply_markup' => $canKeyboard
                        ]);
                        return false;
                    }
                    $weatherUser = \App\Weather::where('chat_id', $user->chat_id)->get()[0];
                    try {
                        $weatherUser->update([
                            'lat' => $input['latitude'],
                            'lon' => $input['longitude'],
                            'location' => Helper::getCityAndCountryGoogle($input['latitude'] . ',' . $input['longitude'])
                        ]);
                    } catch (\Exception $e) {
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => $e->getMessage() . ' Point place where there is any signs of civilization',
                            'reply_markup' => $canKeyboard
                        ]);
                        return true;
                    }
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => 'Successfully set location to ' . $weatherUser->location . ' by coordinates ' . $weatherUser->lat . ' , ' . $weatherUser->lon,
                        'reply_markup' => $canKeyboard
                    ]);
                    break;
            }
        }
        return true;
    }

    static public function deliver(User $user)
    {
        $location = $user->weather->location;
        $response = '';
        $cache = WeatherCache::where('location', $location)->where('units', $user->weather->units)->get();
        if ($cache->isNotEmpty()) {
            $cache = $cache[0];
            $response = $cache->content;
        } else {
            $i = 0;
            while (!Weather::fetch($location, $user->weather->units)) {
                if ($i == 4) {
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => '<strong>OpenWeatherMap.org returned no weather for location "' . ucfirst($location) . '". Sorry for incovenience</strong>',
                        'parse_mode' => 'html'
                    ]);
                    break;
                }
                $i++;
            }

        }

        $all = array();

        if (isset($response)) {
            $cache = WeatherCache::where('location', $location)->where('units', $user->weather->units)->get();
            if ($cache->isNotEmpty()) {
                $cache = $cache[0];
                $all = json_decode($cache->content, true);
            } else
                $all = json_decode($response);

            if (is_array($all)) {
                Telegram::sendMessage([
                    'chat_id' => $user->chat_id,
                    'text' => '<strong>Your daily weather is here here !</strong> "' . $location . '"',
                    'parse_mode' => 'html'
                ]);

                $all = array_slice($all, 0, 8);
                $text = '<strong>' . Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->format('d-m-Y') . '</strong>' . "\n";
                foreach ($all as $entry) {
                    $temp = (((int)$entry['main']['temp_min'] + (int)$entry['main']['temp_max']) / 2);
                    $text .= ($user->weather->units == 'metric' ? Carbon::createFromTimestamp($entry['dt'])->setTimezone($user->schedule->utc)->format('H:i') : Carbon::createFromTimestamp($entry['dt'])->setTimezone($user->schedule->utc)->format('H:i A')) . ' ' . (Helper::sign($temp) == 1 ? '+' : '-') . $temp . ' ';
                    switch ($entry['weather'][0]['description']) {
                        case 'clear sky':
                            $text .= "\u{2600}";
                            break;
                        case 'few clouds':
                            $text .= "\u{1F324}";
                            break;
                        case 'scattered clouds':
                            $text .= "\u{1F325}";
                            break;
                        case 'broken clouds':
                            $text .= "\u{1F325}";
                            break;
                        case 'shower rain':
                            $text .= "\u{1F326}";
                            break;
                        case 'rain':
                            $text .= "\u{1F327}";
                            break;
                        case 'thunderstorm':
                            $text .= "\u{26C8}";
                            break;
                        case 'snow':
                            $text .= "\u{1F328}";
                            break;
                        case 'mist':
                            $text .= "\u{1F32B}";
                            break;
                    }
                    $text .= ' ' . $entry['weather'][0]['main'] . "\n";
                }

                Telegram::sendMessage([
                    'chat_id' => $user->chat_id,
                    'text' => $text,
                    'parse_mode' => 'html',
                    'disable_notification' => true,
                    'reply_markup' => Keyboard::make()
                        ->inline()
                        ->row(
                            Keyboard::inlineButton(['text' => '>' . Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->format('d/m') . '<', 'callback_data' => 'null']),
                            Keyboard::inlineButton(['text' => Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDay()->format('d/m'), 'callback_data' => 'weather 2']),
                            Keyboard::inlineButton(['text' => Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDays(2)->format('d/m'), 'callback_data' => 'weather 3']),
                            Keyboard::inlineButton(['text' => Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDays(3)->format('d/m'), 'callback_data' => 'weather 4']),
                            Keyboard::inlineButton(['text' => Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDays(4)->format('d/m'), 'callback_data' => 'weather 5'])
                        )
                ]);
            }
        }


    }

    static public function scrollMessage(User $user, int $messageId, int $callbackId, int $page)
    {
        $cache = \App\WeatherCache::where('location', $user->weather->location)->where('units', $user->weather->units)->get();
        $response = '';
        if ($cache->isNotEmpty()) {
            $cache = $cache[0];
            $response = $cache->content;
        } else {
            Telegram::editMessageText([
                'chat_id' => $user->chat_id,
                'message_id' => $messageId,
                'text' => '<strong> Can\'t find weather for this date. Seems like they\'ve expired in cache. Use "Force Weather" command to get new instance of weather, or wait for your next daily delivery </strong>',
                'parse_mode' => 'html',
                'disable_notification' => true,
                'reply_markup' => Keyboard::make()
                    ->inline()
                    ->row(
                        Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null']),
                        Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null']),
                        Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null']),
                        Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null']),
                        Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null'])
                    )
            ]);
            $user->update(['function' => null, 'function_state' => null]);
            return false;
        }

        $all = json_decode($response, true);

        $offset = ($page * 8) - 8;

        $weather =  array_slice($all, $offset, 8);

		$text = '<strong>' . Carbon::createFromTimestamp($weather[1]['dt'])->setTimezone($user->schedule->utc)->format('d-m-Y') . '</strong>' . "\n";

        foreach ($weather as $entry) {
            $temp = (((int)$entry['main']['temp_min'] + (int)$entry['main']['temp_max']) / 2);
            $text .= ($user->weather->units == 'metric' ? Carbon::createFromTimestamp($entry['dt'])->setTimezone($user->schedule->utc)->format('H:i') : Carbon::createFromTimestamp($entry['dt'])->format('H:i A')) . ' ' . (Helper::sign($temp) == 1 ? '+' : '-') . $temp . ' ';
//            Telegram::sendMessage([
//          	  'chat_id' => $user->chat_id,
//          	  'text' => print_r($entry['weather'][0]['description'], true)
//            ]);
            switch ($entry['weather'][0]['description']) {
                case 'clear sky':
                    $text .= "\u{2600}";
                    break;
                case 'few clouds':
                    $text .= "\u{1F324}";
                    break;
                case 'scattered clouds':
                    $text .= "\u{1F325}";
                    break;
                case 'broken clouds':
                    $text .= "\u{1F325}";
                    break;
                case 'shower rain':
                    $text .= "\u{1F326}";
                    break;
                case 'rain':
                    $text .= "\u{1F327}qwe";
                    break;
                case 'thunderstorm':
                    $text .= "\u{26C8}";
                    break;
                case 'snow':
                    $text .= "\u{1F328}";
                    break;
                case 'mist':
                    $text .= "\u{1F32B}";
                    break;
            }
            $text .= ' ' . $entry['weather'][0]['main'] . "\n";
        }

        Telegram::answerCallbackQuery([
            'callback_query_id' => $callbackId
        ]);

        Telegram::editMessageText([
            'chat_id' => $user->chat_id,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'html',
            'disable_notification' => true,
            'reply_markup' => Keyboard::make()
                ->inline()
                ->row(
                    ($page == 1 ? Keyboard::inlineButton(['text' => '>' . Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->format('d/m') . '<', 'callback_data' => 'null']) : Keyboard::inlineButton(['text' =>  Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->format('d/m'), 'callback_data' => 'weather 1'])),
                    ($page == 2 ? Keyboard::inlineButton(['text' => '>' . Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDays(1)->format('d/m') . '<', 'callback_data' => 'null']) : Keyboard::inlineButton(['text' =>  Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDays(1)->format('d/m') , 'callback_data' => 'weather 2'])),
                    ($page == 3 ? Keyboard::inlineButton(['text' => '>' . Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDays(2)->format('d/m') . '<', 'callback_data' => 'null']) : Keyboard::inlineButton(['text' =>  Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDays(2)->format('d/m') , 'callback_data' => 'weather 3'])),
                    ($page == 4 ? Keyboard::inlineButton(['text' => '>' . Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDays(3)->format('d/m') . '<', 'callback_data' => 'null']) : Keyboard::inlineButton(['text' =>  Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDays(3)->format('d/m') , 'callback_data' => 'weather 4'])),
                    ($page == 5 ? Keyboard::inlineButton(['text' => '>' . Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDays(4)->format('d/m') . '<', 'callback_data' => 'null']) : Keyboard::inlineButton(['text' =>  Carbon::createFromTimestamp($all[1]['dt'])->setTimezone($user->schedule->utc)->addDays(4)->format('d/m') , 'callback_data' => 'weather 5']))
                )
        ]);
        $user->update(['function' => null, 'function_state' => null]);

        return true;
    }


    static public function fetch($location, $units)
    {
        $weather = WeatherCache::where('location', $location)->where('units', $units)->get();
        if ($weather->isEmpty()) {
            $endpoint = "http://api.openweathermap.org/data/2.5/forecast?q={LOCATION}&appid={API_KEY}&units={UNITS}";
            $endpoint = str_replace("{API_KEY}", env('WEATHER_API_TOKEN'), $endpoint);
            $endpoint = str_replace("{LOCATION}", $location, $endpoint);
            $endpoint = str_replace("{UNITS}", $units, $endpoint);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $endpoint,
                CURLINFO_HEADER_OUT => 1,
                CURLOPT_HTTPHEADER => [
                    'Accept:application/json',
                ]
            ]);

            $response = curl_exec($curl);

            $response = json_decode($response, true);

            if (isset($response['list']) && !empty($response['list'])) {
                WeatherCache::create([
                    'location' => $location,
                    'units' => $units,
                    'content' => json_encode($response['list'])
                ]);
                return true;
            } else
                return false;
        } else
            return true;
    }

}