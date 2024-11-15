<?php

namespace NaturaSiberica\Api\Controllers\Catalog;

use NaturaSiberica\Api\Controllers\AbstractController;
use NaturaSiberica\Api\Services\Catalog\SortService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use Exception;

class SortController
{
    use RequestServiceTrait, ResponseResultTrait;

    private SortService $service;

    public function __construct()
    {
        $this->service = new SortService();
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
