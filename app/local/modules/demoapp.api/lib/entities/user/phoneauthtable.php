<?php

namespace NaturaSiberica\Api\Entities\User;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\EventResult;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\SecretField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\ORM\Fields\Validators\UniqueValidator;
use Bitrix\Main\UserPhoneAuthTable;
use Exception;
use Fig\Http\Message\StatusCodeInterface;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/lib/userphoneauth.php');

class PhoneAuthTable extends DataManager
{
    public static function getTableName()
    {
        return 'ns_api_phone_auth';
    }

    public static function getMap()
    {
        return [

            new StringField("PHONE_NUMBER", [
                'primary'    => true,
                'validation' => function () {
                    return [
                        new LengthValidator(1, null, ["MIN" => Loc::getMessage("user_phone_auth_err_number")]),
                        [UserPhoneAuthTable::class, 'validatePhoneNumber'],
                        new UniqueValidator(Loc::getMessage("user_phone_auth_err_duplicte")),
                    ];
                },
            ]),

            new SecretField("OTP_SECRET", [
                'crypto_enabled' => static::cryptoEnabled("OTP_SECRET"),
            ]),

            new IntegerField("SMS_CODE_SEND_ATTEMPTS", [
                "default_value" => 0,
            ]),
            new IntegerField("REGISTER_ATTEMPTS", [
                "default_value" => 0,
            ]),

            new DatetimeField("DATE_SENT"),
        ];
    }

    public static function onBeforeAdd(Event $event)
    {
        return static::modifyFields($event);
    }

    public static function onBeforeUpdate(Event $event)
    {
        return static::modifyFields($event);
    }

    /**
     * Сброс количества попыток ввода смс при авторизации
     * @param string $phoneNumber
     *
     * @return bool
     * @throws Exception
     */
    public static function reset(string $phoneNumber): bool
    {
        $result = self::delete($phoneNumber);

        if (!empty($result->getErrorMessages())) {
            throw new Exception(implode('. ', $result->getErrorMessages()), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        return $result->isSuccess();
    }

    protected static function modifyFields(Event $event): EventResult
    {
        $fields         = $event->getParameter('fields');
        $result         = new EventResult();
        $modifiedFields = [];

        if (isset($fields["PHONE_NUMBER"])) {
            //normalize the number
            $modifiedFields["PHONE_NUMBER"] = UserPhoneAuthTable::normalizePhoneNumber($fields["PHONE_NUMBER"]);
        }

        $result->modifyFields($modifiedFields);

        return $result;
    }
}
