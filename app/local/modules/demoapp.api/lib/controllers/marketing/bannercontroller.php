<?php

namespace NaturaSiberica\Api\Controllers\Marketing;

use NaturaSiberica\Api\Services\Marketing\BannerService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BannerController
{
    use RequestServiceTrait, ResponseResultTrait;

    private BannerService $service;

    public function __construct()
    {
        $this->service = new BannerService();
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
