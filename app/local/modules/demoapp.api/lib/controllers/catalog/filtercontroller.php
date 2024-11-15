<?php

namespace NaturaSiberica\Api\Controllers\Catalog;

use NaturaSiberica\Api\Services\Catalog\ProductFilterService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FilterController
{
    use RequestServiceTrait, ResponseResultTrait;

    private ProductFilterService $service;

    public function __construct()
    {
        $this->service = new ProductFilterService();
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
