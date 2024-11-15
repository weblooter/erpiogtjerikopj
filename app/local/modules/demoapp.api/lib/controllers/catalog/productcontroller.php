<?php

namespace NaturaSiberica\Api\Controllers\Catalog;

use NaturaSiberica\Api\Interfaces\Controllers\Catalog\ProductControllerInterface;
use NaturaSiberica\Api\Services\Catalog\ProductService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductController implements ProductControllerInterface
{
    use RequestServiceTrait, ResponseResultTrait;

    private ProductService $service;

    public function __construct()
    {
        $this->service = new ProductService();
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
            [$request->getAttribute('code'), $request->getQueryParams()],
            $request
        );
    }

}
