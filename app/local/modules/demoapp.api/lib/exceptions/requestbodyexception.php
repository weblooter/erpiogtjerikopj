<?php

namespace NaturaSiberica\Api\Exceptions;

use Bitrix\Main\Localization\Loc;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Psr7\Factory\ServerRequestFactory;

Loc::loadMessages(__DIR__ . '/serviceexception.php');

class RequestBodyException extends Exception
{
    public function __construct($message = "")
    {
        parent::__construct($message, StatusCodeInterface::STATUS_BAD_REQUEST);
    }

    /**
     */
    public static function validateEmail(string $email)
    {
        $request = ServerRequestFactory::createFromGlobals();
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new HttpBadRequestException($request, Loc::getMessage('EXCEPTION_INCORRECT_EMAIL'));
        }
    }
}
