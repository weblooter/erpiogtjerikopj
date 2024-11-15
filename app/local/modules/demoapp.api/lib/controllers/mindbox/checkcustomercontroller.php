<?php

namespace NaturaSiberica\Api\Controllers\Mindbox;

use NaturaSiberica\Api\Services\Mindbox\CheckCustomerService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CheckCustomerController
{
    use RequestServiceTrait, ResponseResultTrait;

    private CheckCustomerService $service;

    public function __construct()
    {
        $this->service = new CheckCustomerService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse($response, [$this->service, 'index'], [$this->parseRequestBody($request)]);
    }
}
