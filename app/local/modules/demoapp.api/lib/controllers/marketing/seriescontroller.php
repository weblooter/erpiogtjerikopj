<?php

namespace NaturaSiberica\Api\Controllers\Marketing;

use NaturaSiberica\Api\Controllers\AbstractController;
use NaturaSiberica\Api\Interfaces\Controllers\Marketing\BrandControllerInterface;
use NaturaSiberica\Api\Services\Marketing\SeriesService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class SeriesController
{
    use RequestServiceTrait, ResponseResultTrait;

    private SeriesService $service;

    public function __construct()
    {
        $this->service = new SeriesService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->service, 'index'],
            [$request->getQueryParams()],
            $request
        );
    }
}
