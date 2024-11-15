<?php

namespace NaturaSiberica\Api\DTO\Error;

use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\DTO;

class ErrorDTO extends DTO
{
    public string $type = 'error';
    public int    $code;
    public string $message;

    public static function createFromParameters(string $type = 'error', int $code = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR, string $message = 'Unknown error'): ErrorDTO
    {
        $parameters = self::prepareParameters($type, $code, $message);
        return new static($parameters);
    }

    protected static function prepareParameters(
        string $type = 'error',
        int $code = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR,
        string $message = ''
    ): array {
        return [
            'type' => $type,
            'code' => $code,
            'message' => $message,
        ];
    }
}
