<?php

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use NaturaSiberica\Api\App;
use NaturaSiberica\Api\Errors\ApiErrorHandler;
use NaturaSiberica\Api\Errors\Renderer;
use NaturaSiberica\Api\Middlewares\Http\CorsMiddleware;
use Slim\Factory\AppFactory;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loc::loadMessages(dirname(__DIR__) . '/local/modules/demoapp.api/lib/errors/apierrorhandler.php');

try {
    Loader::includeModule('demoapp.api');

    $container = ServiceLocator::getInstance();

    $app = AppFactory::create();
    $app->setBasePath('/api');

    $app->options('/{routes:.+}', function ($request, $response, array $args) {
        return $response;
    });

    $app->addRoutingMiddleware();

    $errorMiddleware = $app->addErrorMiddleware(true, true, true);

    $customErrorHandler = new ApiErrorHandler(
        $app->getCallableResolver(), $app->getResponseFactory()
    );
    $customErrorHandler->forceContentType('application/json');

    $errorMiddleware->setDefaultErrorHandler($customErrorHandler);

    require_once dirname(__DIR__) . '/local/modules/demoapp.api/routes/api.php';

    $app->add(new CorsMiddleware());

    $app->run();
} catch (Exception $e) {
    App::handleErrors($e);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
