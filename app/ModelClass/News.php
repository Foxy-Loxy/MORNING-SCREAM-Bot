<?php


namespace App\ModelClass;

use App\NewsCache;
use App\User;
use Carbon\Carbon;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Exceptions;


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

    static public function deliver(User $user, $article = 0, $messageid = 0, $cat = '')
    {

        if ($article == 0 && $cat == '') {
            $categories = explode(',', $user->news->categories);
            $response = '';
            foreach ($categories as $category) {
                $cache = NewsCache::where('category', $category)->get();
                if ($cache->isNotEmpty()) {
                    $cache = $cache[0];
                    $response = $cache->content;
                } else {
                    $i = 0;
                    while (!News::fetch($category)) {
                        if ($i == 4) {
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => '<strong>Newsapi.org returned 0 news for category "' . ucfirst($category) . '". Sorry for incoveniece</strong>',
                                'parse_mode' => 'html'
                            ]);
                            break;
                        }
                        $i++;
                    }

                }

                $all = array();
				
                if (isset($response)) {
                    $cache = NewsCache::where('category', $category)->get();
                    if ($cache->isNotEmpty()) {
                        $cache = $cache[0];
                        $all = json_decode($cache->content, true);
                    } else
                        $all = json_decode($response);
//					dd($all);
                    if ($article == 0) {
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => '<strong>Your daily news are here !</strong> "' . ucfirst($category) . '"',
                            'parse_mode' => 'html'
                        ]);

                        $art = $all[0];

                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => '<strong>' . $art['title'] . '</strong>' . "\n" .
                                'By: <em>' . $art['source']['name'] . '</em>' . "\n" .
                                'At: ' . Carbon::parse($art['publishedAt'])->setTimezone($user->schedule->utc) . "\n" .
                                $art['description'] . "\n" .
                                '<a href="' . $art['url'] . '">More</a>' . "\n" .
                                'Article 1 of ' . count($all),
                            'parse_mode' => 'html',
                            'disable_notification' => true,
                            'reply_markup' => Keyboard::make()
                                ->inline()
                                ->row(
                                    Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null']),
                                    Keyboard::inlineButton(['text' => 'Next', 'callback_data' => 'article 1 ' . $category])
                                )
                        ]);
                    }

                }
            }
        } else {
            $cache = NewsCache::where('category', $cat)->get();
            if ($cache->isNotEmpty()) {
                $cache = $cache[0];
                $response = $cache->content;
            } else {
                News::fetch($cat);
                $cache = NewsCache::where('category', $cat)->get();
                if ($cache->isNotEmpty()) {
                    $cache = $cache[0];
                    $response = $cache->content;
                }
            }
            $all = json_decode($response, true);
//            throw new \Exception($messageid);
            if (!isset($all[$article - 1]))
                Telegram::editMessageText([
                    'chat_id' => $user->chat_id,
                    'message_id' => $messageid,
                    'text' => '<strong> Can\'t find article by this number </strong>',
                    'parse_mode' => 'html',
                    'disable_notification' => true,
                    'reply_markup' => Keyboard::make()
                        ->inline()
                        ->row(
                            Keyboard::inlineButton(['text' => 'To beginning', 'callback_data' => 'article 0 ' . $cat]),
                            Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null'])
                        )
                ]);
            else {
                $art = $all[$article - 1];
                
                Telegram::editMessageText([
                    'chat_id' => $user->chat_id,
                    'message_id' => $messageid,
                    'text' => '<strong>' . $art['title'] . '</strong>' . "\n" .
                        'By: <em>' . $art['source']['name'] . '</em>' . "\n" .
                        'At: ' . Carbon::parse($art['publishedAt'])->setTimezone($user->schedule->utc) . "\n" .
                        $art['description'] . "\n" .
                        '<a href="' . $art['url'] . '">More</a>' . "\n" .
                        'Article ' . $article . ' of ' . count($all),
                    'parse_mode' => 'html',
                    'disable_notification' => true,
                    'reply_markup' => Keyboard::make()
                        ->inline()
                        ->row(
                            ($article - 1 == 0 ? Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null']) : Keyboard::inlineButton(['text' => 'Previous', 'callback_data' => 'article ' . ($article - 1) . ' ' . $cat])),
                            ($article + 1 > count($all) ? Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null']) : Keyboard::inlineButton(['text' => 'Next', 'callback_data' => 'article ' . ($article + 1) . ' ' . $cat]))
                        )
                ]);

            }

        }
    }

    static public function fetch($category)
    {
  		//dd($category);
        $arts = NewsCache::where('category', $category)->get();
        if ($arts->isEmpty()) {
            $endpoint = "https://newsapi.org/v2/top-headlines?country=ua&apiKey={API_KEY}&category={CATEGORY}&pageSize=10";
            $endpoint = str_replace("{API_KEY}", env('NEWS_API_TOKEN'), $endpoint);
            $endpoint = str_replace("{CATEGORY}", $category, $endpoint);

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

            if (isset($response['articles']) && !empty($response['articles'])) {
                NewsCache::create([
                    'category' => $category,
                    'content' => json_encode($response['articles'])
                ]);
                return true;
            } else
                return false;
        } else
            return true;
    }

}