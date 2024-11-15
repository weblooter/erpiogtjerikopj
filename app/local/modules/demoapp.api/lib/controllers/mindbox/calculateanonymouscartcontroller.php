<?php

namespace NaturaSiberica\Api\Controllers\Mindbox;

use NaturaSiberica\Api\Services\Mindbox\CalculateAnonymousCartService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CalculateAnonymousCartController
{
    use RequestServiceTrait, ResponseResultTrait;

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [new CalculateAnonymousCartService(), 'index'],
            [$this->parseRequestBody($request)]
        );
    }
}
