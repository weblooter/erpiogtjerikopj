<?php

namespace NaturaSiberica\Api\Exceptions;

use Exception;
use Fig\Http\Message\StatusCodeInterface;

class DTOException extends Exception
{
    public function __construct(string $parameter)
    {
        $message = sprintf('Parameter %s not exists or empty', $parameter);

        parent::__construct($message, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
    }
}
