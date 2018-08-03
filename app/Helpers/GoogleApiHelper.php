<?php
/**
 * Created by PhpStorm.
 * User: kavramenko
 * Date: 7/18/2018
 * Time: 2:53 PM
 */

namespace App\Helpers;


use App\Calendar;
use App\User;
use Telegram\Bot\Laravel\Facades\Telegram;

class GoogleApiHelper {

    public static function getClient(User $user, string $key = '') {

        $client = new \Google_Client();
        $client->setApplicationName('Morning Scream Calendar Integration');
        $client->setScopes(\Google_Service_Calendar::CALENDAR_READONLY);
        $client->setAuthConfig(base_path(env('GOOGLE_AUTH_CREDENTIALS_PATH')));
        $client->setAccessType('offline');

        if ( $key != '')
            if (!self::clientAuth($user, $client ,$key))
                return false;

        // Load previously authorized credentials from a file.
        $credentials = $user->calendar;
        if ($credentials != null) {
            $accessToken = json_decode($credentials->data, true);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            return $authUrl;
        }

        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $user->calendar->update([
               'data' =>  json_encode($client->getAccessToken())
            ]);
        }
        return $client;
    }

    public static function clientAuth(User $user, \Google_Client $client, string $code){
        $authCode = trim($code);

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        if (isset($accessToken['error']))
            return false;
        // Store the credentials to disk.
        $cal = $user->calendar;
        if ($user->calendar == null) {
            $cal = Calendar::create([
          		'chat_id' => $user->chat_id,
                'data' => json_encode($accessToken)
            ]);
        } else {
            $cal->update([
                'data' => json_encode($accessToken)
            ]);
        }
        return true;
    }
}