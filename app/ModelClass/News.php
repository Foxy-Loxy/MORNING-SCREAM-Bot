<?php


namespace App\ModelClass;

use App\User;
use Carbon\Carbon;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;


class News
{

    static public function scheduleCall(User $user)
    {
        $catKeyboard = Keyboard::make([
            'keyboard' => [
                ['🗠 Business', '🎶 Entertainment', '🏥 Health'],
                ['🔬 Science', "\u{26BD} Sports", '🖥 Technology'],
                ['📰 General'],
                ['❌ Cancel']
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
        //Since only operation available for this service will be
        $user->update([
            'function' => \App\News::NAME,
            'function_state' => 'WAITING_FOR_CATEGORY'
        ]);
        Telegram::sendMessage([
            'chat_id' => $user->chat_id,
            'text' => 'Choose a category from listed on keyboard',
            'reply_markup' => $catKeyboard
        ]);
    }

    static public function scheduleConfirm(User $user, string $input, Keyboard $exitKbd)
    {
        $catKeyboard = Keyboard::make([
            'keyboard' => [['🗠 Business', '🎶 Entertainment', '🏥 Health'],
                ['🔬 Science', "\u{26BD} Sports", '🖥 Technology'],
                ['📰 General'],
                ['❌ Cancel']],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
        /*    
		Telegram::sendMessage([
      	  'chat_id' => $user->chat_id,
      	  'text' => $input . "\u{26BD}" . ("\u{26BD} Sports" == $input) . ' | ' . ($user->function == \App\News::NAME) . ' | ' .($user->function_state != null)
        ]);
        */
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

                case 'WAITING_FOR_CATEGORY':

                    $news = \App\News::where('chat_id', $user->chat_id)->get();
                    if ($news->isEmpty())
                        $news = \App\News::create([
                            'chat_id' => $user->chat_id,
                            'categories' => null
                        ]);
                    else
                        $news = $news[0];

                    if ($news->categories == null)
                        $catArr = array();
                    else
                        $catArr = explode(',', $news->categories);
                    switch ($input) {
                        case "\u{1F5E0} Business":

                            if (in_array('business', $catArr))
                                unset($catArr[array_search('business', $catArr)]);
                            else
                                $catArr[] = 'business';

                            break;
                        case "\u{1F3B6} Entertainment":

                            if (in_array('entertaiment', $catArr))
                                unset($catArr[array_search('entertrainment', $catArr)]);
                            else
                                $catArr[] = 'entertaiment';

                            break;
                        case "\u{1F3E5} Health":

                            if (in_array('health', $catArr))
                                unset($catArr[array_search('health', $catArr)]);
                            else
                                $catArr[] = 'health';

                            break;
                        case "\u{1F52C} Science":
                            if (in_array('science', $catArr))
                                unset($catArr[array_search('science', $catArr)]);
                            else
                                $catArr[] = 'science';

                            break;
                        case "\u{26BD} Sports":
                            if (in_array('sports', $catArr))
                                unset($catArr[array_search('sports', $catArr)]);
                            else
                                $catArr[] = 'sports';

                            break;
                        case "\u{1F5A5} Technology":

                            if (in_array('technology', $catArr))
                                unset($catArr[array_search('technology', $catArr)]);
                            else
                                $catArr[] = 'technology';

                            break;
                        case "\u{1F4F0} General":

                            if (in_array('general', $catArr))
                                unset($catArr[array_search('general', $catArr)]);
                            else
                                $catArr[] = 'general';

                            break;
                    }
                    $news->update([
                        'categories' => implode(',', $catArr)
                    ]);
                    $list = '';
                    foreach ($catArr as $cat)
                        $list .= ucfirst($cat) . ' | ';
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => 'List of themes you\'ve subscribed: ' . $list,
                        'reply_markup' => $catKeyboard
                    ]);

                    break;

            }
        }
        return true;
    }

    static public function deliver(User $user){

        $endpoint = "https://newsapi.org/v2/top-headlines?country=ua&{API_KEY}&category={CATEGORIES}";
        $endpoint = str_replace("{API_KEY}", env('NEWS_API_TOKEN'), $endpoint);
        $endpoint = str_replace("{CATEGORIES}", $user->news->categories, $endpoint);

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

        Telegram::sendMessage([
            'chat_id' => $user->chat_id,
            'text' => '<strong>Your daily news are here !</strong>',
            'parse_mode' => 'html'
        ]);

        foreach ($response['articles'] as $article)
            Telegram::sendMessage([
                'chat_id' => $user->chat_id,
                'text' => '<strong>' . $article['title'] . '</strong>\n'.
                            'By: <em>' . $article['source']['name'] . '</em> \n'.
                            'At: ' . Carbon::parse($article['publishedAt'])->setTimezone($user->schedule->utc) . '\n' .
                            $article['description'] . '\n' .
                            '<a href="' . $article['url'] .'">More</a>',
                'parse_mode' => 'html',
                'disable_notification' => true
            ]);
    }

}