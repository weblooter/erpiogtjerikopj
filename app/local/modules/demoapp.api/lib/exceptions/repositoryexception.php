<?php

namespace NaturaSiberica\Api\Exceptions;


use Bitrix\Main\ArgumentNullException;
use Fig\Http\Message\StatusCodeInterface;

class RepositoryException extends \Exception
{
    public function __construct($message = "", $code = StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY)
    {
        parent::__construct($message, $code);
    }

    /**
     * @param string $parameterName
     * @param        $data
     *
     * @return bool
     * @throws ArgumentNullException
     */
    public static function assertNotNull(string $parameterName, $data = null): bool
    {
        if ($data === null) {
            throw new ArgumentNullException($parameterName, new static());
        }

        return true;
    }

    /**
     * @throws RepositoryException
     */
    public static function assertNull($data, string $parameter): bool
    {
        if ($data !== null) {
            throw new static(sprintf('%s already exists', $parameter));
        }

        return true;
    }

    /**
     * @throws RepositoryException
     */
    public static function methodNotExists()
    {
        throw new static('Method not exists');
    }
}
