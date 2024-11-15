<?php

namespace NaturaSiberica\Api\Exceptions;

use Bitrix\Main\Localization\Loc;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\Token\RefreshTokenDTO;
use NaturaSiberica\Api\DTO\TokenDTO;

Loc::loadMessages(__FILE__);

class TokenException extends Exception
{
    public function __construct(string $message = '', int $code = StatusCodeInterface::STATUS_UNAUTHORIZED)
    {
        parent::__construct($message, $code);
    }

    /**
     * @param $tokenDTO
     *
     * @return bool
     *
     * @throws TokenException
     */
    public static function validateDTO($tokenDTO): bool
    {
        if ($tokenDTO instanceof TokenDTO) {
            return true;
        }

        throw new static(Loc::getMessage('ERROR_TOKEN_NOT_FOUND'));
    }

    public static function validateFuser(int $fuserId = null)
    {
        if ($fuserId === null) {
            throw new static(Loc::getMessage('error_unknown_fuser'));
        }
    }

    public static function validateRefreshToken(string $refreshToken, RefreshTokenDTO $dto)
    {
        if ($refreshToken !== $dto->token) {
            throw new static(Loc::getMessage('error_invalid_refresh_token'));
        }
    }

    public static function validateTokensInBody(array $body)
    {
        $tokens = ['accessToken', 'refreshToken'];

        foreach ($tokens as $key) {
            if (!array_key_exists($key, $body)) {
                throw new static(Loc::getMessage('error_token_not_found_in_request_body', [
                    '#token#' => str_ireplace('Token', '', $key)
                ]));
            }
        }
    }
}
