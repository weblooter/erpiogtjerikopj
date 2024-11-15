<?php

namespace NaturaSiberica\Api\Interfaces\Services\Token;

use NaturaSiberica\Api\Interfaces\ModuleInterface;

interface TokenServiceInterface extends ModuleInterface
{
    const TOKEN_ALGORITHM_HS256 = 'HS256';

    const DEFAULT_TOKEN_ALGS = [
        self::TOKEN_ALGORITHM_HS256
    ];

    const ERROR_MESSAGE_EXPIRED_TOKEN = 'Expired token';
    const ERROR_MESSAGE_SIGNATURE_VERIFICATION_FAILED = 'Signature verification failed';

    const DEFAULT_DATETIME_FORMAT = 'd-m-Y H:i:s';
    const DEFAULT_DATE_FORMAT = 'd.m.Y';

    const REQUIRED_COUNT_OF_EXPLODED_TOKEN = 2;

    public function setFuserId(int $fuserId): TokenServiceInterface;

    public function getFuserId(): ?int;

    public function setUserId(int $userId): TokenServiceInterface;

    public function getUserId(): ?int;

    public function getToken(array $body = null);

    public function generateNewTokens(): array;

    public function regenerate(string $refreshToken): array;

    /**
     * @param string|array $header
     *
     * @return mixed
     */
    public function checkAccessToken($header);

    public function invalidateToken(string $accessToken): bool;
}
