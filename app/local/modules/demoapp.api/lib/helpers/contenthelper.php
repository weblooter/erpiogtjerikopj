<?php

namespace NaturaSiberica\Api\Helpers;

use NaturaSiberica\Api\Helpers\Http\UrlHelper;

class ContentHelper
{

    public static function replaceImageUrl(string $str)
    {
        if($str) {
            $url = UrlHelper::getUrl();
            return preg_replace_callback(
                '#<img.*src=[\'|\"](.*)[\'|\"].*>#isU', function ($matches) use ($url) {
                $res = $matches[0];
                if (substr($matches[1], 0, 1) === '/') {
                    $res = str_replace($matches[1], trim($url, '/') . '/' . trim($matches[1], '/'), $matches[0]);
                }
                return $res;
            },
                $str
            );
        }
        return '';
    }

    public static function replaceBreaksHtml(string $str)
    {
        return str_ireplace('<br>', PHP_EOL, $str);
    }

}
