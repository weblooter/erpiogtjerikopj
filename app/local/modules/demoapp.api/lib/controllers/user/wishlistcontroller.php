<?php

namespace NaturaSiberica\Api\Controllers\User;

use NaturaSiberica\Api\Interfaces\Controllers\User\FavouritesControllerInterface;
use NaturaSiberica\Api\Services\User\WishListService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class WishListController implements FavouritesControllerInterface
{
    use RequestServiceTrait, ResponseResultTrait;

    private WishListService $wishListService;

    public function __construct()
    {
        $this->wishListService = new WishListService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse($response, [$this->wishListService, 'index'], [$request->getAttribute('userId')]);
    }

    public function addProduct(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->parseRequestBody($request);
        return $this->prepareResponse($response, [$this->wishListService, 'addProduct'], [$request->getAttribute('userId'), $body['items']]);
    }

    public function deleteProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $this->parseRequestBody($request);

        return $this->prepareResponse($response, [$this->wishListService, 'deleteProduct'], [$request->getAttribute('userId'), $args['id']]);
    }

    public function clear(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse($response, [$this->wishListService, 'clear'], [$request->getAttribute('userId')]);
    }

    public function getByUid(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        return $this->prepareResponse(
            $response,
            [$this->wishListService, 'getByUid'],
            [$queryParams['uid']]
        );
    }
}
