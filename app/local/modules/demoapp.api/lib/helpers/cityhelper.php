<?php

namespace NaturaSiberica\Api\Helpers;

use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Helpers\Http\RequestHelper;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Services\ParamsServices;

class CityHelper
{
    const PARAM_NAME = 'city';

    public static function getCityId(): int
    {
        $cityId = RequestHelper::getRequest()->getQuery(self::PARAM_NAME);
        if($cityId) {
            $value = filter_var($cityId, FILTER_VALIDATE_INT);
            if ($value === false) {
                throw new RequestBodyException('Parameter ['.self::PARAM_NAME.'] must be integer');
            }

            if ($value < ConstantEntityInterface::MIN_CITY_VALUE) {
                throw new RequestBodyException(sprintf('Parameter [%s] must be integer greater or equal %s', self::PARAM_NAME, ConstantEntityInterface::MIN_CITY_VALUE));
            }
        } else {
            $cityId = ConstantEntityInterface::DEFAULT_CITY_ID;
        }

        return $cityId;
    }
}
