<?php

namespace NaturaSiberica\Api\Helpers;

class ArrayHelper
{
    public static function removeEmptyItems(array &$array)
    {
        foreach ($array as $key => &$item) {
            if (empty($item)) {
                unset($array[$key]);
            }
        }
    }
}
