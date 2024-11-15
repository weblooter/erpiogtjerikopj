<?php

namespace tests\Helper;

use Osteel\OpenApi\Testing\Exceptions\ValidationException;
use Osteel\OpenApi\Testing\ValidatorBuilder;
use Symfony\Component\HttpFoundation\Response;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Api extends \Codeception\Module
{
    /**
     * Проверяет соответствие тела ответа описанию эндпоинта в OpenAPI-документе (Swagger)
     * 
     * @param string $method HTTP-метод эндпоинта
     * @param string $path Относительный путь к эндпоинту
     * @param string $docPath Путь к OpenAPI-документу
     * 
     * @return void
     */
    public function seeResponseIsValidOnOpenApiDoc(string $method, string $path, string $docPath)
    {
        $restModule = $this->getModule('REST');

        $response = new Response(
            $restModule->grabResponse(),
            $restModule->client->getInternalResponse()->getStatusCode(),
            ['Content-Type' => $restModule->grabHttpHeader('Content-Type')]
        );
        
        $isValid = false;
        $error = '';

        try {
            $validator = ValidatorBuilder ::fromYaml($docPath)->getValidator();
            $isValid = $validator->validate($response, $path, $method);
        } catch (ValidationException $e) {
            $error = sprintf('[%s] %s', get_class($e), $e->getMessage());
        }
        
        \PHPUnit\Framework\Assert::assertTrue($isValid, $error);
    }
}
