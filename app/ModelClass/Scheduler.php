<?php


namespace App\ModelClass;

use App\Helpers\Localize;
use App\Schedule;
use App\User;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;
use Carbon\Carbon;


class Scheduler
{
    static public function scheduleCall(User $user)
    {
        $schedKeyboard = Keyboard::make([
            'keyboard' => [
                ["\u{1F310} Set time zone"],
                ['❌ Cancel']
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $user->update([
            'function' => \App\Schedule::NAME,
            'function_state' => 'WAITING_FOR_TIME'
        ]);

        Telegram::sendMessage([
            'chat_id' => $user->chat_id,
            'text' => 'Enter time you want to receive your daily delivery in format "HH:MM AM\PM". Notice that AM\PM is optional. If you want to specify time in 24-hour format, simply ignore AM\PM. Example: "13:50" OR "1:50 PM"',
            'reply_markup' => $schedKeyboard
        ]);
    }

    static public function scheduleConfirm(User $user, string $input, Keyboard $exitKbd)
    {
        $schedKeyboard = Keyboard::make([
            'keyboard' => [
                ["\u{1F310} Set time zone"],
                ['❌ Cancel']
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $schedTZKeyboard = Keyboard::make([
            'keyboard' => [
                ["\u{23F0} Set time to deliver"],
                ['❌ Cancel']
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

//		dd($user);

        if ($user->function == \App\Schedule::NAME && $user->function_state != null) {
//			dd($user);
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
                case "\u{1F310} Set time zone":
                    $user->update([
                        'function_state' => 'WAITING_FOR_TIMEZONE'
                    ]);
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => 'Enter your current timezone in any standart manner. Example: "UTC", "EEST", "+2:00", "-6"',
                        'reply_markup' => $schedTZKeyboard
                    ]);
					return true;
                    break;
                case "\u{23F0} Set time to deliver":
                    $user->update([
                        'function_state' => 'WAITING_FOR_TIME'
                    ]);
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => 'Enter time you want to receive your daily delivery in format "HH:MM AM\PM". Notice that AM\PM is optional. If you want to specify time in 24-hour format, simply ignore AM\PM. Example: "13:50" OR "1:50 PM"',
                        'reply_markup' => $schedKeyboard
                    ]);
					return true;
                    break;
            }

            $schedule = Schedule::where('chat_id', $user->chat_id)->get();
            if ($schedule->isEmpty())
                $schedule = \App\Schedule::create([
                    'chat_id' => $user->chat_id
                ]);
            else
                $schedule = $schedule[0];

            switch ($user->function_state) {

                case 'WAITING_FOR_TIME':
                    try {
                        $time = Carbon::parse($input)->format('H:i');
                        $schedule->update([
                            'time' => $time
                        ]);
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => 'Delivery time is successfully set to: ' . $time . ' . Notice that you current timezone is set to ' . $schedule->utc,
                            'reply_markup' => $schedKeyboard
                        ]);
                    } catch (\Exception $e) {
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => 'Invalid time format. Example: "13:50" OR "1:50 PM"',
                            'reply_markup' => $schedKeyboard
                        ]);
                        return false;
                    }

                    break;

                case 'WAITING_FOR_TIMEZONE':
                    try {
                        $tz = Carbon::parse($input)->format('P');
                        if (preg_match('/[+-]([01]\d|2[0-4])(:?[0-5]\d)?/m', $tz))
                            $schedule->update([
                                'utc' => $tz
                            ]);
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => 'Timezone was successfully set to ' . $tz . ' UTC',
                            'reply_markup' => $schedTZKeyboard
                        ]);
                    } catch (\Exception $e) {
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => 'Invalid timezone format. Example: "+3" OR "-6:30"',
                            'reply_markup' => $schedTZKeyboard
                        ]);
                        return false;
                    }
                    break;
            }

            $schedule->update([
                'utc_time' => Carbon::parse($schedule->time . $schedule->utc)->setTimezone('UTC')->format('H:i')
            ]);

        }
    }
}