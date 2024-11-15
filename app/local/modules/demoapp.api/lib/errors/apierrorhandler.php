<?php

namespace NaturaSiberica\Api\Errors;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\JWT;
use FastRoute\BadRouteException;
use Fig\Http\Message\StatusCodeInterface;
use Mindbox\Exceptions\MindboxBadRequestException;
use Mindbox\Exceptions\MindboxClientException;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Handlers\ErrorHandler;
use TypeError;

Loader::includeModule('mindbox.marketing');

Loc::loadMessages(__FILE__);
Loc::loadMessages(dirname(__DIR__) . '/traits/http/responseresulttrait.php');

class ApiErrorHandler extends ErrorHandler
{
    use ResponseResultTrait;

    /**
     * @return ResponseInterface
     */
    protected function respond(): ResponseInterface
    {
        $this->addErrorLog($this->exception);

        $errorType = 'error';
        $this->statusCode = $this->exception->getCode() ? : StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY;
        $errorMessage = $this->exception->getMessage();

        $parsedErrors = json_decode($errorMessage, true);

        if (is_array($parsedErrors)) {
            foreach ($parsedErrors as $parsedError) {
                $this->statusCode = $parsedError['code'];
                $this->prepareError($parsedError['code'], $parsedError['type'], $parsedError['message']);
            }
        }

        switch (get_class($this->exception)) {
            case BadRouteException::class:
                $this->statusCode = StatusCodeInterface::STATUS_BAD_REQUEST;
                $errorMessage     = Loc::getMessage('ERROR_INCORRECT_PATH_VARIABLES');
                break;
            case HttpException::class:
                $errorMessage = $this->exception instanceof HttpMethodNotAllowedException ? Loc::getMessage(
                    'error_method_not_allowed'
                ) : $this->exception->getMessage();
                break;
            case TypeError::class:
                $this->statusCode = StatusCodeInterface::STATUS_BAD_REQUEST;
                $errorMessage     = Loc::getMessage('ERROR_INCORRECT_PARAMETERS_TYPE');
                break;
            case \UnexpectedValueException::class:
                $traces = $this->exception->getTrace();
                $trace  = $traces[0];
                $class  = $trace['class'];

                if ($class === JWT::class) {
                    $this->statusCode = StatusCodeInterface::STATUS_UNAUTHORIZED;
                }
                break;
            case MindboxBadRequestException::class:
                $this->statusCode = StatusCodeInterface::STATUS_BAD_REQUEST;
                $errorAsArray     = explode(': ', $this->exception->getMessage());

                if (! empty($errorAsArray[1])) {
                    $errorMessage = $errorAsArray[1];
                }
                break;
            case MindboxClientException::class:
                $this->statusCode = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;
                $errorMessage     = Loc::getMessage('ERROR_MESSAGE_UNKNOWN_SERVER_ERROR');
                break;
        }

        if ($this->exception->getCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR) {
            $this->statusCode = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;
            $errorMessage     = Loc::getMessage('ERROR_MESSAGE_UNKNOWN_SERVER_ERROR');
        }

        if (empty($parsedErrors)) {
            $this->prepareError($this->statusCode, $errorType, $errorMessage);
        }

        $response = $this->responseFactory->createResponse($this->statusCode);
        if ($this->contentType !== null && array_key_exists($this->contentType, $this->errorRenderers)) {
            $response = $response->withHeader('Content-Type', $this->contentType);
        } else {
            $response = $response->withHeader('Content-type', $this->defaultErrorRendererContentType);
        }

        if ($this->exception instanceof HttpMethodNotAllowedException) {
            $allowedMethods = implode(', ', $this->exception->getAllowedMethods());
            $response       = $response->withHeader('Allow', $allowedMethods);
        }

        $json = $this->responseResult();
        $response->getBody()->write($json);

        return $response;
    }
}
