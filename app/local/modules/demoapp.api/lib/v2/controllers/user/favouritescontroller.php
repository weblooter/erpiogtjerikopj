<?php

namespace NaturaSiberica\Api\V2\Controllers\User;

use NaturaSiberica\Api\Interfaces\Controllers\User\FavouritesControllerInterface;
use NaturaSiberica\Api\Interfaces\Services\User\FavouritesServiceInterface;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use NaturaSiberica\Api\V2\Services\User\FavouritesService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FavouritesController
{
    use RequestServiceTrait, ResponseResultTrait;

    private FavouritesService $favouritesService;

    public function __construct()
    {
        $this->favouritesService = new FavouritesService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->favouritesService, 'getItemsByFuserId'],
            [$request->getAttribute('fuserId')]
        );
    }

    public function getByUid(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        return $this->prepareResponse(
            $response,
            [$this->favouritesService, 'getItemsByUid'],
            [$queryParams['uid']]
        );
    }

    public function addProduct(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->parseRequestBody($request);
        $userData = ['userId' => $request->getAttribute('userId'), 'fuserId' => $request->getAttribute('fuserId')];
        return $this->prepareResponse($response, [$this->favouritesService, 'addItems'], [$userData, $body]);
    }

    public function deleteProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $this->parseRequestBody($request);
        return $this->prepareResponse($response, [$this->favouritesService, 'deleteItem'], [$request->getAttribute('fuserId'), $args['id']]);
    }

    public function clear(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse($response,[$this->favouritesService, 'deleteAll'],[$request->getAttribute('fuserId')]);
    }
}
