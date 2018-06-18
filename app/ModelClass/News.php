<?php


namespace App\ModelClass;

use App\User;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;


class News
{
    static public function scheduleCall(User $user)
    {
        //Since only operation available for this service will be
        $user->update([
            'function' => \App\News::NAME,
            'function_args' => 'WAITING_FOR_CATEGORY'
        ]);
        Telegram::message([
            'chat_id' => $user->chat_id,
            'text' => 'Choose a category from listed on keyboard',
            'reply_markup' => Keyboard::make([
                'keyboard' => [['🗠 Business', '🎶 Entertainment', '🏥 Health'],
                    ['🔬 Science', '⚽ Sports', '🖥 Technology'],
                    ['📰 General'],
                    ['❌ Cancel']],
                'resize_keyboard' => true,
                'one_time_keyboard' => true
            ])
        ]);
    }

    static public function scheduleConfirm(User $user, string $input)
    {

        if ($user->function == \App\News::NAME && $user->function_args != null) {

            if ($input == '\\u274c Cancel') {
                $user->update([
                    'function' => null,
                    'function_args' => null
                ]);
                return false;
            }
            switch ($user->function_args) {

                case 'WAITING_FOR_CATEGORY':

                    $news = \App\News::where('chat_id', $user->chat_id)->get();
                    if ($news->isEmpty())
                        $news = \App\News::create([
                            'chat_id' => $user->chat_id,
                            'categories' => null
                        ]);
                    if ($news->categories == null)
                        $catArr = array();
                    else
                        $catArr = explode(',', $news->categories);
                    switch ($input) {
                        case '\\ud83d\\udde0 Business':
                            $catArr[] = 'business';
                            break;
                        case '\\ud83c\\udfb6 Entertainment':
                            $catArr[] = 'entertainment';
                            break;
                        case '\\ud83c\\udfe5 Health':
                            $catArr[] = 'health';
                            break;
                        case '\\ud83d\\udd2c Science':
                            $catArr[] = 'science';
                            break;
                        case '\\u26bd\\ufe0f Sports':
                            $catArr[] = 'sports';
                            break;
                        case '\\ud83d\\udda5 Technology':
                            $catArr[] = 'technology';
                            break;
                        case '\\ud83d\\udcf0 General':
                            $catArr[] = 'general';
                            break;
                        default:
                            return false;
                            break;
                    }
                    $news->update([
                        'categories' => implode(',', $catArr)
                    ]);

                    break;

            }
        }
        $user->update([
            'function' => null,
            'function_args' => null
        ]);
        return false;
    }

}