<?php

namespace NaturaSiberica\Api;

use Bitrix\Main\Config\Option;

class Settings implements Interfaces\ModuleInterface
{
    public static function getDefaultStoreId(): int
    {
        return (int) Option::get(self::MODULE_ID, 'default_store');
    }
}
