<?php

namespace NaturaSiberica\Api\Controllers;

use NaturaSiberica\Api\Services\CityService;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CityController
{
    use ResponseResultTrait;

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $cityService = new CityService();
        return $this->prepareResponse(
            $response,
            [$cityService, 'getCities'],
            [],
            $request
        );
    }
}
