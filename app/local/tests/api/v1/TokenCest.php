<?php

namespace tests\v1;

use Codeception\Util\HttpCode;
use tests\ApiTester;

/**
 * Class TokenCest
 * 
 * @package tests\v2
 */
class TokenCest
{
    /**
     * @var string Относительный путь к эндпоинту
     */
    private string $path = '/api/v1/token';

    /**
     * @var string HTTP-метод эндпоинта
     */
    private string $method = 'POST';

    /**
     * @var string Путь к OpenAPI-документу, описывающему эндпоинт
     */
    private string $docPath = __DIR__ . '/../../../docs/api/user.yaml';

    /**
     * @param ApiTester $I
     *
     * @return void
     */
    public function _before(ApiTester $I)
    {
        $I->setHeader('Content-Type', 'application/json');
    }

    /**
     * Тестируем позитивный кейс
     *
     * @param ApiTester $I
     *
     * @return void
     */
    public function getTokenPositive(ApiTester $I)
    {
        $I->wantTo('Передаем: запрос на получение токена. Ожидаем: токен получен');

        $I->send($this->method, $this->path); // Отправляем запрос

        $I->seeResponseCodeIs(HttpCode::OK); // Смотрим код ответа

        $I->seeResponseIsJson(); // Смотрим формат ответа

        // Смотрим на соответствие ответа схеме OpenAPI-документу (Swagger)   
        $I->seeResponseIsValidOnOpenApiDoc($this->method, $this->path, $this->docPath);
    }
}
