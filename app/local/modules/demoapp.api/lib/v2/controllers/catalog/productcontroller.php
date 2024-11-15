<?php

namespace NaturaSiberica\Api\V2\Controllers\Catalog;

use NaturaSiberica\Api\Interfaces\Controllers\Catalog\ProductControllerInterface;
use NaturaSiberica\Api\Interfaces\Services\Catalog\ProductServiceInterface;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use NaturaSiberica\Api\V2\Services\Catalog\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductController implements ProductControllerInterface
{
    use RequestServiceTrait, ResponseResultTrait;

    private ProductServiceInterface $productService;

    public function __construct()
    {
        $this->productService = new ProductService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->productService, 'index'],
            [$request->getQueryParams()],
            $request
        );
    }


    public function get(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->productService, 'get'],
            [$request->getAttribute('code'), $request->getQueryParams()],
            $request
        );
    }
}
