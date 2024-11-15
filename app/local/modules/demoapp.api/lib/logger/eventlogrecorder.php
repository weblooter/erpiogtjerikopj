<?php

namespace NaturaSiberica\Api\Logger;

use Bitrix\Main\IO\File;
use CEventLog;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Slim\Interfaces\RouteInterface;
use Slim\MiddlewareDispatcher;
use Slim\Routing\Route;

class EventLogRecorder implements ModuleInterface
{
    public static function addRequestLog(ServerRequestInterface $request)
    {
        /**
         * @var Route $route
         */
        $route = $request->getAttribute('__route__');
        $body = json_decode((string) $request->getBody(), true);

        $data = [
            'URI' => self::prepareUriData($request->getUri()),
            'Headers' => $request->getHeaders(),
            'Route' => self::prepareRouteData($route)
        ];

        if (!empty($body)) {
            $data['Body'] = $body;
        }

        CEventLog::Add([
            'SEVERITY' => 'INFO',
            'AUDIT_TYPE_ID' => self::EVENT_LOG_AUDIT_TYPE_ID_REQUEST_INFO,
            'MODULE_ID' => self::MODULE_ID,
            'ITEM_ID' => implode('::', $route->getCallable()),
            'DESCRIPTION' => self::prepareDescription($data)
        ]);


    }

    public static function addErrorLog(\Throwable $exception)
    {
        $trace = $exception->getTrace();
        $error = self::prepareExceptionData($exception);

        $log = [
            'SEVERITY' => 'ERROR',
            'AUDIT_TYPE_ID' => self::EVENT_LOG_AUDIT_TYPE_ID_API_ERROR,
            'MODULE_ID' => self::MODULE_ID,
            'ITEM_ID' => self::prepareItemId($trace),
            'DESCRIPTION' => self::convertArrayToMessage($error)
        ];

        CEventLog::Add($log);
    }

    private static function prepareDescription(array $data): string
    {
        $description = '';

        foreach ($data as $title => $value) {
            $description .= sprintf('%s<br>', self::convertArrayToMessage($value, $title));
        }

        return $description;
    }

    private static function convertArrayToMessage(array $data, string $title = null): string
    {
        $message = '';

        if (gettype($title) === 'string' && strlen($title) > 0) {
            $message .= sprintf('%s<br><br>', $title);
        }



        foreach ($data as $key => &$value) {
            if (empty($value)) {
                continue;
            }
            if (is_array($value)) {

                if (count($value) > self::ONE) {
                    $value = sprintf(
                        '[%s]',
                        implode(', ', $value)
                    );
                } else {
                    $value = $value[0];
                }

            }



            $message .= sprintf('%s: %s<br>', $key, $value);
        }

        return $message;
    }

    private static function prepareItemId(array $traces, string $ignoredMethod = null): string
    {
        $ignoredMethod = str_ireplace([__CLASS__, ':'], '', $ignoredMethod);

        $ignoredClasses = [__CLASS__, MiddlewareDispatcher::class];
        $ignoredMethods = ['handle', '{closure}', 'process', 'run', 'include_once', $ignoredMethod];

        foreach ($traces as $trace) {

            if (in_array($trace['class'], $ignoredClasses) || in_array($trace['function'], $ignoredMethods)) {
                continue;
            }

            return sprintf('%s%s%s', $trace['class'], $trace['type'], $trace['function']);
        }

        return '';
    }

    private static function prepareUriData(UriInterface $uri): array
    {
        return [
            'scheme' => $uri->getScheme(),
            'host' => $uri->getHost(),
            'port' => $uri->getPort(),
            'query' => $uri->getQuery(),
            'path' => $uri->getPath(),
            'authority' => $uri->getAuthority(),
            'fragment' => $uri->getFragment(),
            'user_info' => $uri->getUserInfo()
        ];
    }

    private static function prepareExceptionData(\Throwable $exception)
    {
        return [
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
        ];
    }

    private static function prepareRouteData(RouteInterface $route): array
    {
        return [
            'name' => $route->getName(),
            'methods' => $route->getMethods(),
            'pattern' => $route->getPattern(),
            'callable' => $route->getCallable(),
            'arguments' => $route->getArguments(),
        ];
    }
}
