<?php

namespace NaturaSiberica\Api\Controllers\Sale;

use NaturaSiberica\Api\Controllers\AbstractController;
use NaturaSiberica\Api\Factories\Sale\CartFactory;
use NaturaSiberica\Api\Interfaces\Controllers\Sale\CartControllerInterface;
use NaturaSiberica\Api\Services\Sale\CartService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CartController implements CartControllerInterface
{
    use RequestServiceTrait, ResponseResultTrait;

    private CartService $cartService;

    public function __construct()
    {
        $this->cartService = new CartService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->cartService, 'index'],
            [$request->getAttribute('fuserId')]
        );
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->parseRequestBody($request);
        return $this->prepareResponse(
            $response,
            [$this->cartService, 'update'],
            [$request->getAttribute('fuserId'), $body['productItems']]
        );
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->cartService, 'delete'],
            [$request->getAttribute('fuserId')]
        );
    }
}
