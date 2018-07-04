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

    private $locale_strings;

    public function __construct(string $locale) {
        try {
            $this->locale_strings = json_decode(file_get_contents(base_path('app/Locales/' . $locale . '.json')), true);
        } catch (\Exception $e) {
            $this->locale_strings = json_decode(file_get_contents(base_path('app/Locales/en.json')), true);
        }
    }

    public function getString(string $str)
    {
        if (empty($str))
            return '';
        if (!isset($this->locale_strings[$str]))
            return $str;
        return $this->locale_strings[$str];
    }

    public function getAllLocales()
    {
        $localeArr = array_diff(scandir(base_path('app/Locales')), array('..', '.'));
        $result = array();
        foreach ($localeArr as $locale) {
            try {
                $data = json_decode(file_get_contents(base_path('app/Locales/' . $locale . '.json')), true);
                if ($data != null) {
                    $tmp = array();
                    $tmp['short'] = $data['shortLang'];
                    $tmp['full'] = $data['lang'];
                    $result[] = $tmp;
                }
            } catch (\Exception $e) {
                continue;
            }
            return $result;
        }
    }
}