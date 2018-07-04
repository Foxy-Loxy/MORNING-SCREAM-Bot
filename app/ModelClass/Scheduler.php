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
        $locale = app(Localize::class);
        $schedKeyboard = Keyboard::make([
            'keyboard' => [
                [$locale->getString('scheduler_menu_SetTZKbd')],
                [$locale->getString('cancel')]
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
            'text' => $locale->getString('scheduler_menu_SetTZ_Enter'),
            'reply_markup' => $schedKeyboard
        ]);
    }

    static public function scheduleConfirm(User $user, string $input, Keyboard $exitKbd)
    {
        $locale = app(Localize::class);
        $schedKeyboard = Keyboard::make([
            'keyboard' => [
                [$locale->getString('scheduler_menu_SetTZKbd')],
                [$locale->getString('cancel')]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

        $schedTZKeyboard = Keyboard::make([
            'keyboard' => [
                [$locale->getString('scheduler_menu_SetDelivTimeKbd')],
                [$locale->getString('cancel')]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ]);

//		dd($user);

        if ($user->function == \App\Schedule::NAME && $user->function_state != null) {
//			dd($user);
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
                case $locale->getString('scheduler_menu_SetTZKbd'):
                    $user->update([
                        'function_state' => 'WAITING_FOR_TIMEZONE'
                    ]);
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => $locale->getString('scheduler_menu_SetTZ_Enter'),
                        'reply_markup' => $schedTZKeyboard
                    ]);
					return true;
                    break;
                case $locale->getString('scheduler_menu_SetDelivTimeKbd'):
                    $user->update([
                        'function_state' => 'WAITING_FOR_TIME'
                    ]);
                    Telegram::sendMessage([
                        'chat_id' => $user->chat_id,
                        'text' => $locale->getString('scheduler_menu_SetDelivTime_Enter'),
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
                            'text' => $locale->getString('scheduler_SetDelivTime_Success') . $time . $locale->getString('scheduler_SetDelivTime_Notice') . $schedule->utc,
                            'reply_markup' => $schedKeyboard
                        ]);
                    } catch (\Exception $e) {
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => $locale->getString('scheduler_SetDelivTime_Fail'),
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
                            'text' => $locale->getString('scheduler_SetTZ_Success') . $tz . ' UTC',
                            'reply_markup' => $schedTZKeyboard
                        ]);
                    } catch (\Exception $e) {
                        Telegram::sendMessage([
                            'chat_id' => $user->chat_id,
                            'text' => $locale->getString('scheduler_SetTZ_Fail'),
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