<?php

namespace NaturaSiberica\Api\Exceptions\Http;

use Fig\Http\Message\StatusCodeInterface;

class HttpUnprocessableEntityException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
    }
}
