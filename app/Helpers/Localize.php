<?php
/**
 * Created by PhpStorm.
 * User: kavramenko
 * Date: 6/27/2018
 * Time: 1:51 PM
 */

namespace App\Helpers;


class Localize
{
        static public function getStringByLocale(string $loc, string $str){
      		if (empty($str))
      			return '';
            $locStr = $loc . '.' . $str;
            $args = explode('.', $locStr);
            $locale = ( isset($args[0]) ? $args[0] : Helper::throwException('Can\'t resolve locale name') );
            $string = ( isset($args[1]) ? $args[1] : Helper::throwException('Can\'t resolve string name') );

            $json = json_decode(file_get_contents(base_path('app/Locales/' . $locale . '.json')), true);
            return $json[$string];
        }

        static public function getAllLocaleNames(){
			
        }
}