<?php
/**
 * Created by PhpStorm.
 * User: kavramenko
 * Date: 6/22/2018
 * Time: 11:50 AM
 */

namespace App\Helpers;

use App\User;

class Helper
{
    public static function getUserData(array $requestArray): array
    {
        $resultArray = array();
        array_walk_recursive($requestArray, function ($array, $key) use (&$resultArray) {
            if ($key == 'from' && isset($array['is_bot'])) {
                if ($array['is_bot'] == true)
                    return false;
                if (!empty($resultArray))
                    return false;
                $resultArray['chat_id'] = $array['id'];
                $resultArray['first_name'] = $array['first_name'];
                $resultArray['last_name'] = $array['last_name'];
                $resultArray['username'] = (isset($array['username']) ? $array['username'] : null);
                return true;
            }
            return false;
        });
        if (empty($resultArray))
            throw new \Exception('Cannot find user with request data given');
        return $resultArray;
    }

    static public function getInputData(array $requestArray): array
    {
        $resultArray = array();
        $keys = array_keys($requestArray);
        $key = ( isset($keys[1]) ? $keys[1] : null );
        if ($key == null)
            throw new \Exception('Cannot find input with request data given');
        else {
            $resultArray['type'] = $key;
            $resultArray['data'] = $resultArray[$key];
        }
        return $resultArray;
    }


}