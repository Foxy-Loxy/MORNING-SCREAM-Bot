<?php
/**
 * Created by PhpStorm.
 * User: kavramenko
 * Date: 7/18/2018
 * Time: 2:53 PM
 */

namespace App\Helpers;


use App\Calendar;
use App\ModelClass\User;

class GoogleApiHelper {

    public static function getClient(User $user) {

        $client = new \Google_Client();
        $client->setApplicationName('Morning Scream Calendar Integration');
        $client->setScopes(\Google_Service_Calendar::CALENDAR_READONLY);
        $client->setAuthConfig(env(''));
        $client->setAccessType('offline');

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

        // Store the credentials to disk.
        $cal = $user->calendar;
        if ($user->calendar == null) {
            $cal = Calendar::create([
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