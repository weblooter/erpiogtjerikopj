<?php

namespace NaturaSiberica\Api\Interfaces\Services;

use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
/*
 * Генерация
 * - собрать payload
 * - JWT::encode
 *
 *
 * Перевыпуск
 * - проверка
 * - удалить все предыдущие токены для данного пользователя/покупателя
 * - генерация нового токена
 *
 *
 * Валидация
 *
 * свойства:
 * - fuserId
 *
 *
 * Payload:
 * - int fuser_id
 * - int|null user_id
 */

/**
 * Интерфейс для реализации сервисов работы с токенами.
 *
 * Используются access-токен и refresh-токен
 *
 * Основные операции:
 * <ul>
 * <li>Получение токенов из запроса</li>
 * <li>Проверка токенов</li>
 * <li>формирование payload в токене</li>
 * <li>Генерация новых токенов</li>
 * </ul>
 */
interface TokenServiceInterface extends ServiceInterface
{
    const TOKEN_ALGORITHM_HS256 = 'HS256';

    const ERROR_MESSAGE_EXPIRED_TOKEN = 'Expired token';
    const ERROR_MESSAGE_SIGNATURE_VERIFICATION_FAILED = 'Signature verification failed';

    const DEFAULT_DATETIME_FORMAT = 'd-m-Y H:i:s';
    const DEFAULT_DATE_FORMAT = 'd.m.Y';

    const REQUIRED_COUNT_OF_EXPLODED_TOKEN = 2;

    /**
     * Основной метод сервиса.
     *
     * Из запроса вытаскиваются access- и refresh-токены, проверяются на валидность, и, в случае необходимости, генерируются новые
     *
     * @param string|array $authHeader
     *
     * @return mixed
     */
    public function generate($authHeader);

    /**
     * Получение нового токена
     *
     * @param string $type Тип токена (access/refresh)
     *
     * @return string
     */
    public function getNewToken(string $type): string;

    /**
     * Извлечение полезной нагрузки из токена
     *
     * @param string $token
     * @param bool   $array
     */
    public function extractPayloadFromToken(string $token, bool $array = true);

    /**
     * Извлечение токена из заголовка
     *
     * @param string|array $header
     *
     * @return string
     */
    public function extractTokenFromHeader($header): string;

    /**
     * @param array|null $row
     * @param bool       $useAccessToken
     * @param bool       $useRefreshToken
     *
     * @return mixed
     */
    public function generateNewTokens(array $row = null, bool $useAccessToken = true, bool $useRefreshToken = true);

    /**
     * Удаление токенов из БД
     * @param DTOInterface $object
     *
     * @return bool
     */
    public function invalidateTokens(DTOInterface $object): bool;

    /**
     * @param int $ts
     *
     * @return bool
     */
    public function checkTokenExpiration(int $ts): bool;
}
