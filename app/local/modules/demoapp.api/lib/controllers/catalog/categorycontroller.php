<?php

namespace NaturaSiberica\Api\Controllers\Catalog;

use NaturaSiberica\Api\Controllers\AbstractController;
use NaturaSiberica\Api\Services\Catalog\CategoryService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use Exception;

class CategoryController
{
    use RequestServiceTrait, ResponseResultTrait;

    private CategoryService $service;

    public function __construct()
    {
        $this->service = new CategoryService();
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

    public function get(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->service, 'get'],
            [$request->getAttribute('id'), $request->getQueryParams()],
            $request
        );
    }
}
