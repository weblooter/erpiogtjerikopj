<?php

namespace NaturaSiberica\Api\Validators\Sale;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem\Service;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Validators\Validator;

Loc::loadMessages(__FILE__);

class PaymentValidator extends Validator
{
    /**
     * @param Service $service
     *
     * @return void
     * @throws Exception
     */
    public static function validatePaySystemIsCash(Service $service): void
    {
        if ($service->isCash()) {
            throw new Exception(
                Loc::getMessage('error_pay_system_is_cash'),
                StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
            );
        }
    }
}
