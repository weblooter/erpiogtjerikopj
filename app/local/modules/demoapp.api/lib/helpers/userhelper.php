<?php

namespace NaturaSiberica\Api\Helpers;

use Bitrix\Main\Application;

class UserHelper
{
    public static function clear()
    {
        $session = Application::getInstance()->getSession();

        global $USER;

        if ((isset($USER) && $USER instanceof \CUser) && $USER->IsAuthorized()) {
            $USER->Logout();
        } else {
            $session->destroy();
        }
    }

    public static function preparePhoneForQuery(string $phone): string
    {
        return '%' . self::trimPhone($phone);
    }

    public static function trimPhone(string $phone)
    {
        $phone = str_ireplace([' ', '-', '(', ')'], '', $phone);
        return substr($phone, 1);
    }

    public static function preparePhoneForRegistration(string $phone): string
    {
        return sprintf(
            '+7%s',
            self::trimPhone($phone)
        );
    }
}
