<?php

namespace NaturaSiberica\Api\Controllers\Marketing;

use NaturaSiberica\Api\Controllers\AbstractController;
use NaturaSiberica\Api\Interfaces\Controllers\Marketing\BrandControllerInterface;
use NaturaSiberica\Api\Services\Marketing\BrandService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class BrandController
{
    use RequestServiceTrait, ResponseResultTrait;

    private BrandService $service;

    public function __construct()
    {
        $this->service = new BrandService();
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

    public function getMainBrandProducts(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->service, 'getMainBrandProducts'],
            [$request->getQueryParams()],
            $request
        );
    }

}
