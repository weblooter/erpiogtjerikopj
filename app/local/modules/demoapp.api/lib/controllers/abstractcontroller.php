<?php

namespace NaturaSiberica\Api\Controllers;

use Bitrix\Main\DI\ServiceLocator;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use NaturaSiberica\Api\Interfaces\ControllerInterface;
use Slim\Factory\AppFactory;

abstract class AbstractController implements ControllerInterface, ModuleInterface
{
    protected ContainerInterface $container;
    protected App $app;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->setContainer($container);
        $this->initApp();
    }

    /**
     * @param ContainerInterface|null $container
     */
    private function setContainer(ContainerInterface $container = null): void
    {
        if ($container === null) {
            $container = ServiceLocator::getInstance();
        }

        $this->container = $container;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initApp()
    {
        if($this->container->has('slim.app')) {
            $this->app = $this->container->get('slim.app');
        } else {
            $this->app = AppFactory::create(null, $this->container);
        }
    }

    /**
     * Получение тела запроса
     *
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    protected function prepareRequestBody(ServerRequestInterface $request): array
    {
        return json_decode((string)$request->getBody(), true);
    }
}
