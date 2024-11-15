<?php

use Bitrix\Rest\AuthTypeException;
use Fig\Http\Message\StatusCodeInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;

return [
    AuthTypeException::class => [
        'code' => StatusCodeInterface::STATUS_BAD_REQUEST,
        'type' => 'bad_request'
    ],
    UnexpectedValueException::class => [
        'code' => StatusCodeInterface::STATUS_UNAUTHORIZED,
        'type' => 'undefined_token'
    ],
    HttpNotFoundException::class => [
        'code' => StatusCodeInterface::STATUS_NOT_FOUND,
        'type' => 'not_found'
    ],
    HttpMethodNotAllowedException::class => [
        'code' => StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED,
        'type' => 'method_not_allowed'
    ],

];
