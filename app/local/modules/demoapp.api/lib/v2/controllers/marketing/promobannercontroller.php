<?php

namespace NaturaSiberica\Api\V2\Controllers\Marketing;

use NaturaSiberica\Api\V2\Services\Marketing\PromoBannerService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PromoBannerController
{
    use RequestServiceTrait, ResponseResultTrait;

    private PromoBannerService $service;

    public function __construct()
    {
        $this->service = new PromoBannerService();
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
