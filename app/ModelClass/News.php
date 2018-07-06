<?php


namespace App\ModelClass;

use App\Helpers\Localize;
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
        $locale = app(Localize::class);
        $menuKeyboard = Keyboard::make([
            'keyboard' => [
                [$locale->getString('news_menu_SetCountry')],
                [$locale->getString('news_menu_SetCat')],
                [$locale->getString('cancel')]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
/*
        $catKeyboard = Keyboard::make([
            'keyboard' => [
                [$locale->getString('news_cat_BusinessKbd'), $locale->getString('news_cat_EntertrainmentKbd'), $locale->getString('news_cat_HealthKbd')],
                [$locale->getString('news_cat_ScienceKbd'), $locale->getString('news_cat_SportsKbd'), $locale->getString('news_cat_TechnologyKbd')],
                [$locale->getString('news_cat_GeneralKbd')],
                [$locale->getString('cancel')]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
*/

        //Since only operation available for this service will be
        $user->update([
            'function' => \App\News::NAME,
            'function_state' => 'WAITING_FOR_CATEGORY_MENU'
        ]);

        $tmp = \App\News::where('chat_id', $user->chat_id)->get();
        if($tmp->isEmpty)
            \App\News::create([
                'chat_id' => $user->chat_id
            ]);

        Telegram::sendMessage([
            'chat_id' => $user->chat_id,
            'text' => $locale->getString('news_menu_Enter'),
            'reply_markup' => $menuKeyboard
        ]);
    }

    static public function scheduleConfirm(User $user, string $input, Keyboard $exitKbd)
    {
        $locale = app(Localize::class);

        $catKeyboard = Keyboard::make([
            'keyboard' => [
                [$locale->getString('news_cat_BusinessKbd'), $locale->getString('news_cat_EntertrainmentKbd'), $locale->getString('news_cat_HealthKbd')],
                [$locale->getString('news_cat_ScienceKbd'), $locale->getString('news_cat_SportsKbd'), $locale->getString('news_cat_TechnologyKbd')],
                [$locale->getString('news_cat_GeneralKbd')],
                [$locale->getString('cancel')]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $menuKeyboard = Keyboard::make([
            'keyboard' => [
                [$locale->getString('news_menu_SetCountry')],
                [$locale->getString('news_menu_SetCat')],
                [$locale->getString('cancel')]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);
        $countKeyboard = Keyboard::make([
                'keyboard' => [
                    [$locale->getString('cancel')],
                    ['ae', 'ar', 'at', 'au', 'be', 'bg'],
                    ['br', 'ca', 'ch', 'cn', 'co', 'cu'],
                    ['cz', 'de', 'eg', 'fr', 'gb', 'gr'],
                    ['hk', 'hu', 'id', 'ie', 'il', 'in'],
                    ['it', 'jp', 'kr', 'lt', 'lv', 'ma'],
                    ['mx', 'my', 'ng', 'nl', 'no', 'nz'],
                    ['ph', 'pl', 'pt', 'ro', 'rs', 'ru'],
                    ['sa', 'se', 'sg', 'si', 'sk' ,'th'],
                    ['tr', 'tw', 'ua', 'us', 've', 'za']
                ]
            ]);




        if ($user->function == \App\News::NAME && $user->function_state != null) {

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

                case 'WAITING_FOR_COUNTRY':
                    $countries = array(
                                        'ae', 'ar', 'at', 'au', 'be', 'bg',
                                        'br', 'ca', 'ch', 'cn', 'co', 'cu',
                                        'cz', 'de', 'eg', 'fr', 'gb', 'gr',
                                        'hk', 'hu', 'id', 'ie', 'il', 'in',
                                        'it', 'jp', 'kr', 'lt', 'lv', 'ma',
                                        'mx', 'my', 'ng', 'nl', 'no', 'nz',
                                        'ph', 'pl', 'pt', 'ro', 'rs', 'ru',
                                        'sa', 'se', 'sg', 'si', 'sk', 'th',
                                        'tr', 'tw', 'ua', 'us', 've', 'za'
                    );
                    if (in_array($input, $countries)){
                        $user->news->update([
                            'country' => $input
                        ]);
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => $locale->getString('news_SetCountry_Success') . strtoupper($user->news->country),
                            'reply_markup' => $countKeyboard
                        ]);
                        return true;
                    } else {
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => $locale->getString('news_SetCountry_Fail'),
                            'reply_markup' => $countKeyboard
                        ]);
                        return false;
                    }
                    break;

                case 'WAITING_FOR_CATEGORY_MENU':
                    switch ($input) {

                        case $locale->getString('news_menu_SetCountry'):
                            $user->update([
                                'function' => \App\News::NAME,
                                'function_state' => 'WAITING_FOR_COUNTRY'
                            ]);
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => $locale->getString('news_Country_Enter'),
                                'reply_markup' => $countKeyboard
                            ]);
                            return true;
                            break;

                        case $locale->getString('news_menu_SetCat'):
                            $user->update([
                                'function' => \App\News::NAME,
                                'function_state' => 'WAITING_FOR_CATEGORY'
                            ]);
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => $locale->getString('news_choose'),
                                'reply_markup' => $catKeyboard
                            ]);
                            return true;
                            break;

                        default:
                            Telegram::sendMessage([
                                'chat_id' => $user->chat_id,
                                'text' => $locale->getString('news_menu_Fail'),
                                'reply_markup' => $menuKeyboard
                            ]);
                            return false;
                    }
                    break;

                case 'WAITING_FOR_CATEGORY':

                    $news = \App\News::where('chat_id', $user->chat_id)->get();
                    if ($news->isEmpty())
                        $news = \App\News::create([
                            'chat_id' => $user->chat_id,
                        ]);
                    else
                        $news = $news[0];

                    if ($news->categories == null)
                        $catArr = array();
                    else
                        $catArr = explode(',', $news->categories);
                    switch ($input) {
                        case $locale->getString('news_cat_BusinessKbd'):

                            if (in_array('business', $catArr))
                                unset($catArr[array_search('business', $catArr)]);
                            else
                                $catArr[] = 'business';

                            break;
                        case $locale->getString('news_cat_EntertrainmentKbd'):

                            if (in_array('entertainment', $catArr))
                                unset($catArr[array_search('entertrainment', $catArr)]);
                            else
                                $catArr[] = 'entertainment';

                            break;
                        case $locale->getString('news_cat_HealthKbd'):

                            if (in_array('health', $catArr))
                                unset($catArr[array_search('health', $catArr)]);
                            else
                                $catArr[] = 'health';

                            break;
                        case $locale->getString('news_cat_ScienceKbd'):
                            if (in_array('science', $catArr))
                                unset($catArr[array_search('science', $catArr)]);
                            else
                                $catArr[] = 'science';

                            break;
                        case $locale->getString('news_cat_SportsKbd'):
                            if (in_array('sports', $catArr))
                                unset($catArr[array_search('sports', $catArr)]);
                            else
                                $catArr[] = 'sports';

                            break;
                        case $locale->getString('news_cat_TechnologyKbd'):

                            if (in_array('technology', $catArr))
                                unset($catArr[array_search('technology', $catArr)]);
                            else
                                $catArr[] = 'technology';

                            break;
                        case $locale->getString('news_cat_GeneralKbd'):

                            if (in_array('general', $catArr))
                                unset($catArr[array_search('general', $catArr)]);
                            else
                                $catArr[] = 'general';

                            break;
                    }
                    $news->update([
                        'categories' => implode(',', $catArr)
                    ]);
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => $locale->getString('news_list') . $user->news->FancyCategories(),
                        'reply_markup' => $catKeyboard
                    ]);

                    break;

            }
        }
        return true;
    }

    static public function deliver(User $user)
    {
        $locale = app(Localize::class);

        $categories = explode(',', $user->news->categories);
        $response = '';
        foreach ($categories as $category) {
            $cache = NewsCache::where('category', $category)->get();
            if ($cache->isNotEmpty()) {
                $cache = $cache[0];
                $response = $cache->content;
            } else {
                $i = 0;
                while (!News::fetch($category, $user->news->country)) {
                    if ($i == 4) {
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => $locale->getString('news_delivery_ZeroResults') . $locale->getString($category),
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

				if (is_array($all)){


                Telegram::sendMessage([
                    'chat_id' => $user->chat_id,
                    'text' => $locale->getString('news_delivery_Delivery') . $locale->getString($category),
                    'parse_mode' => 'html'
                ]);
				
                $art = $all[0];

                Telegram::sendMessage([
                    'chat_id' => $user->chat_id,
                    'text' => '<strong>' . $art['title'] . '</strong>' . "\n" .
                        $locale->getString('news_delivery_By') . '<em>' . $art['source']['name'] . '</em>' . "\n" .
                        $locale->getString('news_delivery_At') . Carbon::parse($art['publishedAt'])->setTimezone($user->schedule->utc) . "\n" .
                        $art['description'] . "\n" .
                        '<a href="' . $art['url'] . '">'. $locale->getString('news_delivery_More') .'</a>' . "\n" .
                        $locale->getString('news_delivery_NewsNoBegin') . '1' . $locale->getString('news_delivery_NewsNoEnd') . count($all),
                    'parse_mode' => 'html',
                    'disable_notification' => true,
                    'reply_markup' => Keyboard::make()
                        ->inline()
                        ->row(
                            Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null']),
                            Keyboard::inlineButton(['text' => $locale->getString('news_delivery_Next'), 'callback_data' => 'article 2 ' . $category])
                        )
                ]);
                }
            }

        }
    }


    static public function scrollMessage(User $user, int $article, int $messageId, int $callbackId, string $cat)
    {
        $locale = app(Localize::class);
        
        $cache = NewsCache::where('category', $cat)->get();
        if ($cache->isNotEmpty()) {
            $cache = $cache[0];
            $response = $cache->content;
        } else {
            Telegram::editMessageText([
                'chat_id' => $user->chat_id,
                'message_id' => $messageId,
                'text' => $locale->getString('news_delivery_CacheEmpty'),
                'parse_mode' => 'html',
                'disable_notification' => true,
                'reply_markup' => Keyboard::make()
                    ->inline()
                    ->row(
                        Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null']),
                        Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null'])
                    )
            ]);
            $user->update(['function' => null, 'function_state' => null]);
            return false;
        }

        $all = json_decode($response, true);

        if (!isset($all[$article - 1]))
            Telegram::editMessageText([
                'chat_id' => $user->chat_id,
                'message_id' => $messageId,
                'text' => $locale->getString('news_delivery_NoPage'),
                'parse_mode' => 'html',
                'disable_notification' => true,
                'reply_markup' => Keyboard::make()
                    ->inline()
                    ->row(
                        Keyboard::inlineButton(['text' => $locale->getString('news_delivery_Beginning'), 'callback_data' => 'article 0 ' . $cat]),
                        Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null'])
                    )
            ]);
        else {
            $art = $all[$article - 1];
            
          Telegram::answerCallbackQuery([
        		'callback_query_id' => $callbackId
          ]);

          Telegram::editMessageText([
                'chat_id' => $user->chat_id,
                'message_id' => $messageId,
              'text' => '<strong>' . $art['title'] . '</strong>' . "\n" .
                  $locale->getString('news_delivery_By') . '<em>' . $art['source']['name'] . '</em>' . "\n" .
                  $locale->getString('news_delivery_At') . Carbon::parse($art['publishedAt'])->setTimezone($user->schedule->utc) . "\n" .
                  $art['description'] . "\n" .
                  '<a href="' . $art['url'] . '">'. $locale->getString('news_delivery_More') .'</a>' . "\n" .
                  $locale->getString('news_delivery_NewsNoBegin') . $article . $locale->getString('news_delivery_NewsNoEnd') . count($all),
                'parse_mode' => 'html',
                'disable_notification' => true,
                'reply_markup' => Keyboard::make()
                    ->inline()
                    ->row(
                        ($article - 1 == 0 ? Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null']) : Keyboard::inlineButton(['text' => $locale->getString('news_delivery_Prev'), 'callback_data' => 'article ' . ($article - 1) . ' ' . $cat])),
                        ($article + 1 > count($all) ? Keyboard::inlineButton(['text' => '-', 'callback_data' => 'null']) : Keyboard::inlineButton(['text' => $locale->getString('news_delivery_Next'), 'callback_data' => 'article ' . ($article + 1) . ' ' . $cat]))
                    )
            ]);
            $user->update(['function' => null, 'function_state' => null]);
        }
        return true;
    }

    static public function fetch($category, $country)
    {
        $arts = NewsCache::where('category', $category)->where('country', $country)->get();
        if ($arts->isEmpty()) {
            $endpoint = "https://newsapi.org/v2/top-headlines?country={COUNTRY}&apiKey={API_KEY}&category={CATEGORY}&pageSize=10";
            $endpoint = str_replace("{API_KEY}", env('NEWS_API_TOKEN'), $endpoint);
            $endpoint = str_replace("{CATEGORY}", $category, $endpoint);
            $endpoint = str_replace("{COUNTRY}", $country, $endpoint);

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
                    'content' => json_encode($response['articles']),
                    'country' => $country
                ]);
                return true;
            } else
                return false;
        } else
            return true;
    }

}