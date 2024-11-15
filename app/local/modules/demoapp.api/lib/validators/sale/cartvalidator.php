<?php

namespace NaturaSiberica\Api\Validators\Sale;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Basket;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Validators\Validator;

Loc::loadMessages(__FILE__);

class CartValidator extends Validator
{
    /**
     * @throws Exception
     */
    public static function validateEmptyBasket(Basket $basket)
    {
        if (empty($basket->toArray())) {
            throw new Exception(Loc::getMessage('error_empty_basket'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }
    }
}
