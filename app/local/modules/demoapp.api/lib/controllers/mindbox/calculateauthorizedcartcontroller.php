<?php

namespace NaturaSiberica\Api\Controllers\Mindbox;

use NaturaSiberica\Api\Services\Mindbox\CalculateAuthorizedCartService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CalculateAuthorizedCartController
{
    use RequestServiceTrait, ResponseResultTrait;

    private CalculateAuthorizedCartService $service;

    public function __construct()
    {
        $this->service = new CalculateAuthorizedCartService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse($response, [$this->service, 'index'], [$this->parseRequestBody($request)]);
    }
}
