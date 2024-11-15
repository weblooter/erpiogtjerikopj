<?php

namespace NaturaSiberica\Api\Controllers\Marketing;

use NaturaSiberica\Api\Services\Marketing\CollectionService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CollectionController
{
    use RequestServiceTrait, ResponseResultTrait;

    private CollectionService $service;

    public function __construct()
    {
        $this->service = new CollectionService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response, [$this->service, 'index'],
            [$request->getQueryParams()],
            $request
        );
    }
}
