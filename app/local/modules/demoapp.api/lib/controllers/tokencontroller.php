<?php

namespace NaturaSiberica\Api\Controllers;

use NaturaSiberica\Api\DTO\TokenDTO;
use NaturaSiberica\Api\Factories\TokenFactory;
use NaturaSiberica\Api\Interfaces\Services\TokenServiceInterface;
use NaturaSiberica\Api\Services\TokenService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TokenController
{
    use RequestServiceTrait, ResponseResultTrait;

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ts = new \NaturaSiberica\Api\Services\Token\TokenService();
        $body = $this->parseRequestBody($request);

        return $this->prepareResponse(
            $response,
            [$ts, 'getToken'],
            [$body]
        );
    }
}
