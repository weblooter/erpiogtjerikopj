<?php

namespace NaturaSiberica\Api\Exceptions;


use Bitrix\Main\Localization\Loc;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\User\UserDTO;
use Slim\Exception\HttpBadRequestException;
use Slim\Psr7\Factory\ServerRequestFactory;

Loc::loadMessages(__FILE__);

class ServiceException extends Exception
{
    protected static function getRequest()
    {
        return ServerRequestFactory::createFromGlobals();
    }

    public function __construct($message = "", $code = StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY)
    {
        parent::__construct($message, $code);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public static function validateEmail(string $email): bool
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        throw new HttpBadRequestException(static::getRequest(), Loc::getMessage('EXCEPTION_INCORRECT_EMAIL'));
    }

    /**
     * @param bool|null   $subscribedToEmail
     * @param string $email
     *
     * @return bool
     * @throws ServiceException
     */
    public static function validateEmailNotifications(UserDTO $userDTO, bool $subscribedToEmail = null): bool
    {
        if ($subscribedToEmail === true && empty($userDTO->email)) {
            throw new static(Loc::getMessage('EXCEPTION_EMPTY_EMAIL'));
        }

        return true;
    }
}
