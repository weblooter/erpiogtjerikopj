<?php

namespace NaturaSiberica\Api\Controllers\Mindbox;

use NaturaSiberica\Api\Services\Mindbox\MergeCustomersService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MergeCustomersController
{
    use RequestServiceTrait, ResponseResultTrait;

    private MergeCustomersService $service;

    public function __construct()
    {
        $this->service = new MergeCustomersService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse($response, [$this->service, 'index'], [$this->parseRequestBody($request)]);
    }
}
