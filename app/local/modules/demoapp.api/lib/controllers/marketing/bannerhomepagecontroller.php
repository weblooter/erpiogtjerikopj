<?php

namespace NaturaSiberica\Api\Controllers\Marketing;

use NaturaSiberica\Api\Services\Marketing\BannersHomepageService;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BannerHomepageController
{
    use ResponseResultTrait;

    private BannersHomepageService $service;

    public function __construct()
    {
        $this->service = new BannersHomepageService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse($response, [$this->service, 'index']);
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        return $this->prepareResponse($response, [$this->service, 'get'], [$args['position']]);
    }
}
