<?php

namespace NaturaSiberica\Api\Controllers\Catalog;

use NaturaSiberica\Api\Services\Catalog\FastFilterService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FastFilterController
{
    use RequestServiceTrait, ResponseResultTrait;

    private FastFilterService $service;

    public function __construct()
    {
        $this->service = new FastFilterService();
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
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
