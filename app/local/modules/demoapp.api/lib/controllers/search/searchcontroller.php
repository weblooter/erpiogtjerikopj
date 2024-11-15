<?php

namespace NaturaSiberica\Api\Controllers\Search;

use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Interfaces\Controllers\Search\SearchControllerInterface;
use NaturaSiberica\Api\Services\Search\SearchService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SearchController implements SearchControllerInterface
{
    use RequestServiceTrait, ResponseResultTrait;

    private SearchService $service;

    public function __construct()
    {
        $this->service = new SearchService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->service, 'index'],
            [$request->getQueryParams()]
        );
    }
}
