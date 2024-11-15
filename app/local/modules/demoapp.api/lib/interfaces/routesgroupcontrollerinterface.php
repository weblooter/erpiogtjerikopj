<?php

namespace NaturaSiberica\Api\Interfaces;

use Slim\Interfaces\RouteCollectorProxyInterface;

interface RoutesGroupControllerInterface
{
    public function __invoke(RouteCollectorProxyInterface $group);
}
