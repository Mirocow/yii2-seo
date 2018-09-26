<?php

namespace mirocow\seo\helpers;

class UrlHelper
{
    public static function clean($url)
    {
        $info = parse_url($url);

        if(empty($info['path']) && empty($url)){
            throw new Exception('Route path is wrong');
        }

        $query = [];

        if(isset($info['query']) && is_string($info['query'])) {
            if (function_exists('mb_parse_str')) {
                mb_parse_str($info['query'], $query);
            } else {
                parse_str($info['query'], $query);
            }
        }

        $query = self::cleanArray($query);

        return $info['path'] . ($query? '?' . http_build_query($query): '');
    }

    protected static function cleanArray($array)
    {
        if (is_array($array))
        {
            foreach ($array as $key => $sub_array)
            {
                $result = self::cleanArray($sub_array);
                if ($result === false)
                {
                    unset($array[$key]);
                }
                else
                {
                    $array[$key] = $result;
                }
            }
        }

        if (empty($array))
        {
            return false;
        }

        return $array;
    }

}