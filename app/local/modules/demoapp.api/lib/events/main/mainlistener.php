<?php

namespace NaturaSiberica\Api\Events\Listeners\Listeners;

use Bitrix\Main\Loader;
use NaturaSiberica\Api\Interfaces\ModuleInterface;

class MainListener implements ModuleInterface
{
    private static array $modules = [
        'demoapp.api',
        'iblock',
        'highloadblock',
        'catalog',
        'sale'
    ];
    public static function OnPageStart()
    {
        foreach (self::$modules as $module) {
            Loader::includeModule($module);
        }
    }
}
