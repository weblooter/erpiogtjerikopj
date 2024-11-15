<?php

namespace NaturaSiberica\Api\Exceptions;

use Fig\Http\Message\StatusCodeInterface;

class CollectionException extends \Exception
{
    private static string $undefinedItemMessage = 'Undefined item';

    /**
     * @param array|null $item
     *
     * @return bool
     *
     * @throws CollectionException
     */
    public static function assertItem(array $item = null): bool
    {
        if (!is_array($item)) {
            throw new static(static::$undefinedItemMessage, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        return true;
    }
}
