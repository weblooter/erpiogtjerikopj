<?php

namespace NaturaSiberica\Api\Services\Token;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\JWT;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\Token\AccessTokenDTO;
use NaturaSiberica\Api\DTO\Token\RefreshTokenDTO;
use NaturaSiberica\Api\Entities\Tokens\TokensTable;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Exceptions\TokenException;
use NaturaSiberica\Api\Interfaces\Services\Token\TokenServiceInterface;
use NaturaSiberica\Api\Repositories\Token\TokenRepository;
use NaturaSiberica\Api\Repositories\User\FuserRepository;
use ReflectionException;

Loc::loadMessages(__FILE__);

class TokenService implements TokenServiceInterface
{

    private FuserRepository $fuserRepository;
    private TokenRepository $tokenRepository;

    private ?int $fuserId = null;
    private ?int $userId  = null;

    private array $payload = [];

    private array $responseResult = [];

    /**
     * @param int|null $fuserId
     * @param int|null $userId
     */
    public function __construct(?int $fuserId = null, ?int $userId = null)
    {
        $this->fuserId = $fuserId;
        $this->userId  = $userId;

        $this->fuserRepository = new FuserRepository();
        $this->tokenRepository = new TokenRepository();
    }

    private function createAccessToken(): string
    {
        $this->preparePayload();

        $key = $this->getAccessTokenSecretKey();

        return JWT::encode($this->payload, $key, static::TOKEN_ALGORITHM_HS256);
    }

    /**
     * @return TokenServiceInterface
     *
     * @throws TokenException
     */
    private function preparePayload(): TokenServiceInterface
    {
        TokenException::validateFuser($this->fuserId);

        $this->payload['fuserId'] = $this->fuserId;

        if ($this->userId !== null) {
            $this->payload['userId'] = $this->userId;
        }

        $this->payload['iat'] = time();
        $this->payload['exp'] = $this->getTokenTtl('access');

        return $this;
    }

    private function createRefreshToken(string $accessToken): string
    {
        $accessTokenPartsLength = 10;
        $accessTokenOffsetStart = 0;
        $accessTokenOffsetEnd   = -10;
        $refreshTokenLength     = 32;
        $refreshTokenOffsetEnd  = -32;

        return substr(
            md5(
                sprintf(
                    '%s%s',
                    md5(substr($accessToken, $accessTokenOffsetStart, $accessTokenPartsLength)),
                    md5(substr($accessToken, $accessTokenOffsetEnd, $accessTokenPartsLength))
                )
            ),
            $refreshTokenOffsetEnd,
            $refreshTokenLength
        );
    }

    /**
     * @return int|null
     */
    public function getFuserId(): ?int
    {
        return $this->fuserId;
    }

    /**
     * @param int|null $fuserId
     *
     * @return TokenService
     *
     * @throws ArgumentException
     * @throws ServiceException
     * @throws SystemException
     * @throws ReflectionException
     */
    public function setFuserId(?int $fuserId = null): TokenService
    {
        if ($fuserId === null) {
            $fuserId = $this->fuserRepository->getId();
        }
        $this->fuserId = $fuserId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param int|null $userId
     *
     * @return TokenService
     */
    public function setUserId(?int $userId = null): TokenService
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @param array|null $body
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws Exception
     * @throws ReflectionException
     * @throws ServiceException
     * @throws SystemException
     * @throws TokenException
     */
    public function getToken(array $body = null): array
    {
        /**
         * Пустое тело запроса
         */
        if (empty($body)) {
            return $this->createFuser()->generateNewTokens();
        }

        TokenException::validateTokensInBody($body);

        $this->validateAccessTokenByRefreshToken($body['accessToken'], $body['refreshToken']);
        $this->validateRefreshTokenInDB($body['refreshToken']);
        $this->validateRefreshTokenExpirationTime($body['refreshToken']);

        $payload = $this->extractPayloadFromAccessToken($body['accessToken'], false);

        TokenException::validateFuser($payload->fuserId);

        $this->setFuserId($payload->fuserId);
        $this->setUserId($payload->userId);

        return $this->generateNewTokens();
    }

    /**
     * Генерация нового токена
     *
     * @return array
     *
     * @throws TokenException|Exception
     */
    public function generateNewTokens(): array
    {
        $accessTokenDTO  = $this->createAccessTokenDTO();
        $refreshTokenDTO = $this->createRefreshTokenDTO($accessTokenDTO->token);

        $this->tokenRepository->create($refreshTokenDTO);

        $this->prepareResponseResult('accessToken', $accessTokenDTO->toArray());
        $this->prepareResponseResult('refreshToken', $refreshTokenDTO->except('fuserId')->toArray());
        $this->prepareResponseResult('message', Loc::getMessage('success_token_created'));

        return $this->responseResult;
    }

    /**
     * Перевыпуск токена
     *
     * @param string $refreshToken
     *
     * @return array
     */
    public function regenerate(string $refreshToken): array
    {
        // TODO: на данный момент метод не используется, но в будущем может пригодиться
        return [];
    }

    /**
     * @param string $type
     * @param bool   $returnDateTimeObject
     *
     * @return DateTime|int
     */
    private function getTokenTtl(string $type, bool $returnDateTimeObject = false)
    {
        $ttl = time() + (int)Option::get(self::MODULE_ID, sprintf('%s_token_ttl', $type));
        return $returnDateTimeObject ? DateTime::createFromTimestamp($ttl) : $ttl;
    }

    public function extractAccessToken($header)
    {
        if (is_array($header)) {
            $header = $header[0];
        }

        return str_ireplace('Bearer ', '', $header);
    }

    /**
     * @return string
     */
    private function getAccessTokenSecretKey(): string
    {
        return Option::get(self::MODULE_ID, 'access_token_secret_key');
    }

    private function createFuser(): TokenService
    {
        $this->fuserId = $this->fuserRepository->getId();
        return $this;
    }

    private function createAccessTokenDTO(): AccessTokenDTO
    {
        $token = $this->createAccessToken();
        return AccessTokenDTO::create($token, $this->payload['exp']);
    }

    private function createRefreshTokenDTO(string $accessToken): RefreshTokenDTO
    {
        return RefreshTokenDTO::create([
            'fuserId' => $this->fuserId,
            'refreshToken'   => $this->createRefreshToken($accessToken),
            'created' => DateTime::createFromTimestamp(time()),
            'expires' => $this->getTokenTtl('refresh', true),
        ]);
    }

    private function checkTokenExpiration(int $ts): bool
    {
        return $ts > time();
    }

    /**
     * @param string $accessToken
     * @param string $refreshToken
     *
     * @return void
     *
     * @throws Exception
     */
    private function validateAccessTokenByRefreshToken(string $accessToken, string $refreshToken)
    {
        if ($refreshToken !== $this->createRefreshToken($accessToken)) {
            throw new Exception(Loc::getMessage('error_access_token_by_refresh_token'), StatusCodeInterface::STATUS_UNAUTHORIZED);
        }
    }

    /**
     * @param string $refreshToken
     *
     * @return void
     * @throws Exception
     */
    private function validateRefreshTokenInDB(string $refreshToken)
    {
        $refreshTokenDTO = $this->tokenRepository->findByRefreshToken($refreshToken)->get();

        if (! $refreshTokenDTO) {
            throw new Exception(Loc::getMessage('error_refresh_token_not_found'), StatusCodeInterface::STATUS_UNAUTHORIZED);
        }
    }

    /**
     * @param string $refreshToken
     *
     * @return void
     * @throws Exception
     */
    private function validateRefreshTokenExpirationTime(string $refreshToken)
    {
        $refreshTokenDTO = $this->tokenRepository->findByRefreshToken($refreshToken)->get();

        if ($refreshTokenDTO->expires->getTimestamp() < time()) {
            throw new Exception(Loc::getMessage('error_expired_refresh_token'), StatusCodeInterface::STATUS_UNAUTHORIZED);
        }
    }

    /**
     * @param string $token
     * @param bool   $array
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function extractPayloadFromAccessToken(string $token, bool $array = true)
    {
        $explodedToken = explode('.', $token);

        [$head, $payload] = $explodedToken;

        if (count($explodedToken) < self::REQUIRED_COUNT_OF_EXPLODED_TOKEN) {
            throw new Exception(Loc::getMessage('error_incorrect_access_token'), StatusCodeInterface::STATUS_UNAUTHORIZED);
        }

        return json_decode(JWT::urlsafeB64Decode($payload), $array);
    }

    private function getPayloadFromAccessToken(string $accessToken): object
    {
        return JWT::decode($accessToken, $this->getAccessTokenSecretKey(), self::DEFAULT_TOKEN_ALGS);
    }

    private function prepareResponseResult(string $key, $value): TokenService
    {
        $this->responseResult[$key] = $value;
        return $this;
    }

    /**
     * Проверка access-токена
     *
     * @param string $accessToken
     *
     * @return void
     *
     * @throws ArgumentException
     * @throws ReflectionException
     * @throws ServiceException
     * @throws SystemException
     * @throws TokenException
     */
    public function checkAccessToken($header)
    {
        $accessToken = $this->extractAccessToken($header);
        $payload     = $this->getPayloadFromAccessToken($accessToken);

        TokenException::validateFuser($payload->fuserId);

        $this->setFuserId($payload->fuserId);
        $this->setUserId($payload->userId);
    }

    /**
     * @return bool
     */
    public function invalidateToken(string $accessToken): bool
    {
        $refreshToken = $this->createRefreshToken($accessToken);
        $row          = TokensTable::getList([
            'filter' => ['refreshToken' => $refreshToken],
            'select' => ['id'],
        ])->fetchObject();

        if ($row) {
            return $this->tokenRepository->delete($row->getId());
        }

        return true;
    }
}
