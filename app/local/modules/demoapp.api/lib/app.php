<?php

namespace NaturaSiberica\Api;

use Bitrix\Main\HttpResponse;
use Error;
use Exception;
use NaturaSiberica\Api\Services\Http\ResponseResultService;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Slim\App as SlimApp;
use Slim\Factory\AppFactory;
use NaturaSiberica\Api\Traits\ModuleTrait;
use TypeError;

final class App
{
    use ModuleTrait;

    private SlimApp $app;
    private ContainerInterface $container;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->initSlimApp();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function initSlimApp()
    {
        if($this->container->has('slim.app')) {
            $this->app = $this->container->get('slim.app');
        } else {
            $this->app = AppFactory::create(null, $this->container);
        }

        $this->app->setBasePath('/api');
    }

    public function run()
    {
        $this->requireRoutesFromFile($this->app, 'api');
        $this->app->run();
    }

    /**
     * Обработка ошибок
     *
     * @param Exception|Error|TypeError $exception
     * @param HttpResponse|null         $response
     */
    public static function handleErrors($exception, HttpResponse &$response = null)
    {
        if ($response === null) {
            $response = new HttpResponse();
        }

        $service = new ResponseResultService();

        $error = $service->prepareErrorData($exception);

        $service->prepareError($error['code'], $error['type'], $error['message']);

        try {
            if ($response->getHeaders()->getContentType() !== 'application/json') {
                $response->addHeader('Content-Type', 'application/json');
            }

            $response
                ->setStatus($error['code'])
                ->setContent($service->responseResult());
        } catch (Exception|Error|TypeError $e) {
            self::handleErrors($e, $response);
        }

        $response->send();
    }
}
