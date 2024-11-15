<?php

namespace NaturaSiberica\Api\Events\Listeners\Main;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use CUser;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Events\Listeners\Listener;
use NaturaSiberica\Api\Validators\User\UserValidator;

Loc::loadMessages(__FILE__);

class UserListener extends Listener
{
    /**
     * @param array $fields
     *
     * @return void
     *
     * @throws Exception
     */
    public static function OnAfterUserAdd(array &$fields)
    {
        if (self::$isHandlerDisallowed) {
            return;
        }

        self::allowHandler();

        $userId = (int)$fields['ID'];

        UserValidator::validateUser($userId, $fields['RESULT_MESSAGE'], StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);

        $user = new CUser();
        $user->Update($userId, [
            'UF_UID' => md5($userId),
        ]);

        self::disallowHandler();
    }

    /**
     * @param array $fields
     *
     * @return void
     *
     * @throws ArgumentException
     * @throws Exception
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function OnBeforeUserUpdate(array &$fields)
    {
        if (self::$isHandlerDisallowed) {
            return;
        }

        self::allowHandler();

        $userId = (int)$fields['ID'];

        UserValidator::validateUser($userId, Loc::getMessage('error_unknown_user'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);

        $user = UserTable::getByPrimary($userId, [
            'select' => ['ID', 'UF_UID'],
        ])->fetchObject();

        if (empty($user->get('UF_UID'))) {
            $fields['UF_UID'] = md5($userId);
        }

        self::disallowHandler();
    }
}
