<?php

namespace NaturaSiberica\Api\Traits\Http;

use Bitrix\Main\Localization\Loc;
use CEventLog;
use Error;
use Fig\Http\Message\StatusCodeInterface;
use Exception;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Logger\EventLogRecorder;
use NaturaSiberica\Api\Traits\Entities\CacheTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Response;
use Throwable;
use TypeError;

Loc::loadMessages(__FILE__);

trait ResponseResultTrait
{
    use CacheTrait;

    protected int $responseStatusCode = 200;

    protected array $responseResult = [
        'success' => true,
        'data'    => [],
        'errors'  => [],
    ];

    private function getExceptionList(): array
    {
        return require_once dirname(__DIR__, 2) . '/configs/exceptions.php';
    }

    public function setResponseStatusCode(int $code)
    {
        $this->responseStatusCode = $code;
    }

    /**
     * @return int
     */
    public function getResponseStatusCode(): int
    {
        return $this->responseStatusCode;
    }

    /**
     * Формирование статуса ответа (успешный/неуспешный)
     *
     * @param bool $success
     *
     * @return void
     */
    public function setResponseSuccessStatus(bool $success)
    {
        $this->responseResult['success'] = $success;
    }

    public function addResponseData(array $data)
    {
        if ($this->responseResult['data'] === null || empty($this->responseResult['data'])) {
            $this->setResponseData($data);
            return;
        }

        $this->responseResult['data'] = array_merge_recursive($this->responseResult['data'], $data);
    }

    /**
     * Формирование содержимого ответа
     *
     * @param array|null $data
     *
     * @return void
     */
    public function setResponseData(array $data = null)
    {
        $this->responseResult['data'] = $data;
    }

    /**
     * Добавление ошибки в ответ
     *
     * @param array $error
     *
     * @return void
     */
    public function addResponseError(array $error)
    {
        $this->responseResult['errors'][] = $error;
    }

    /**
     * Формирование ответа с ошибкой
     *
     * @param int    $code
     * @param string $errorType
     * @param string $message
     *
     * @return void
     */
    public function prepareError(int $code, string $errorType, string $message)
    {
        if ($code < StatusCodeInterface::STATUS_BAD_REQUEST || $code >= StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR) {
            $errorType = 'error';
            $message   = $this->getErrorMessage($message) ?? Loc::getMessage('ERROR_MESSAGE_UNKNOWN_SERVER_ERROR');
        }

        $this->setResponseStatusCode($code);
        $this->setResponseData();
        $this->setResponseSuccessStatus(false);

        $error = [
            'type'    => $errorType,
            'code'    => $code,
            'message' => $message,
        ];
        $this->addResponseError($error);
    }

    /**
     * Конвертированный в строку массив $responseResult
     *
     * @return string
     */
    public function responseResult(): string
    {
        return json_encode($this->responseResult, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Формирование массива с ошибкой
     *
     * @param Exception $exception
     *
     * @return array
     */
    public function prepareErrorData(Exception $exception): array
    {
        $enabledResponseCode = (int)$exception->getCode() >= StatusCodeInterface::STATUS_BAD_REQUEST && (int)$exception->getCode(
            ) < StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR;

        return [
            'message' => $exception->getMessage(),
            'code'    => $enabledResponseCode ? $exception->getCode() : StatusCodeInterface::STATUS_BAD_REQUEST,
            'type'    => 'error',
        ];
    }

    /**
     * @param ResponseInterface $response
     * @param array             $callback
     * @param array             $arguments
     *
     * @return ResponseInterface
     */
    public function prepareResponse(
        ResponseInterface $response,
        array $callback,
        array $arguments = [],
        ServerRequestInterface $request = null
    ): ResponseInterface {
        $data      = call_user_func_array($callback, $arguments);
        $cacheData = $this->addCache($request, $data);
        $this->addResponseData($data);
        $body = $this->responseResult();
        $response->getBody()->write($body);

        if ($cacheData) {
            return $response->withHeader('Cache-Control', 'public, max-age=' . $cacheData['cacheTTL'])->withHeader(
                'ETag',
                '"' . $cacheData['cacheKey'] . '"'
            )->withHeader('Last-Modified', gmdate('D, j M Y H:i:s T', $cacheData['createdAt']))->withStatus($this->responseStatusCode);
        }

        return $response;
    }

    public function addRequestLog(ServerRequestInterface $request)
    {
        EventLogRecorder::addRequestLog($request);
    }

    /**
     * @param Throwable $exception
     *
     * @return void
     */
    public function addErrorLog(Throwable $exception)
    {
        EventLogRecorder::addErrorLog($exception);
    }

    protected function getErrorMessages()
    {
        return [
            'Expired token' => Loc::getMessage('ERROR_MESSAGE_EXPIRED_TOKEN'),
        ];
    }

    public function getErrorMessage(string $message): ?string
    {
        $messages = $this->getErrorMessages();
        return $messages[$message];
    }
}
