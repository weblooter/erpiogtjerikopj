<?php

namespace NaturaSiberica\Api\Errors;

use Bitrix\Main\Localization\Loc;
use FastRoute\BadRouteException;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Slim\Error\AbstractErrorRenderer;
use Slim\Exception\HttpSpecializedException;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;
use TypeError;

Loc::loadMessages(__FILE__);
Loc::loadMessages(dirname(__DIR__) . '/traits/http/responseresulttrait.php');

class Renderer extends AbstractErrorRenderer implements ErrorRendererInterface
{
    use ResponseResultTrait;
    /**
     * @param Throwable $exception
     * @param bool      $displayErrorDetails
     *
     * @return string
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails = true): string
    {
        $this->addErrorLog($exception);

        $rc = new \ReflectionClass($exception);
        $errorType = 'error';
        $errorCode = StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;
        $errorMessage = '';

        if ($rc->getName() === BadRouteException::class) {
            $errorMessage = Loc::getMessage('ERROR_INCORRECT_PATH_VARIABLES');
            $errorCode = StatusCodeInterface::STATUS_BAD_REQUEST;
        }

        if (is_object($rc->getParentClass()) && $rc->getParentClass()->getName() === HttpSpecializedException::class) {
            $errorMessage = $exception->getMessage();
            $errorCode = $exception->getCode();
        }

        if ($rc->getName() === TypeError::class) {
            $errorCode = StatusCodeInterface::STATUS_BAD_REQUEST;
            $errorMessage = Loc::getMessage('ERROR_INCORRECT_PARAMETERS_TYPE');
        } elseif ($exception->getCode() >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR) {
            $errorMessage = Loc::getMessage('ERROR_MESSAGE_UNKNOWN_SERVER_ERROR');
        }

        $this->prepareError($errorCode, $errorType, $errorMessage);

        return $this->responseResult();
    }
}
