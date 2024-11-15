<?php

namespace NaturaSiberica\Api\Exceptions;

use Exception;

class RoutesControllerException extends Exception
{
    /**
     * @throws RoutesControllerException
     */
    public static function assertEmptyRoutes(array $routes)
    {
        if(empty($routes)) {
            throw new static('Array of routes must be not empty');
        }
    }

    /**
     * @throws RoutesControllerException
     */
    public static function assertEmptyRouterControllers(array $routerControllers)
    {
        if(empty($routerControllers)) {
            throw new static('Array of routerControllers must be not empty');
        }
    }
}
