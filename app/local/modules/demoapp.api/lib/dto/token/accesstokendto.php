<?php

namespace NaturaSiberica\Api\DTO\Token;

use Spatie\DataTransferObject\DataTransferObject;

class AccessTokenDTO extends DataTransferObject
{
    public string $type = 'bearer';
    public string $token;
    public int $created;
    public int $expires;

    public static function create(string $accessToken, int $expires)
    {
        return new static([
            'token' => $accessToken,
            'created' => time(),
            'expires' => $expires
        ]);
    }
}
