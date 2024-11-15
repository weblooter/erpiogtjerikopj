<?php

namespace NaturaSiberica\Api\Validators;

use Bitrix\Main\ArgumentNullException;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Interfaces\SerializerInterface;

abstract class AbstractValidator
{
    /**
     * @param string $key
     * @param array  $data
     *
     * @return bool
     * @throws ServiceException
     */
    public static function assertNotEmpty(string $key, array $data): bool
    {
        if (empty($data[$key])) {
            throw new ServiceException(sprintf('Required parameter %s not exists', $key));
        }

        return true;
    }
}