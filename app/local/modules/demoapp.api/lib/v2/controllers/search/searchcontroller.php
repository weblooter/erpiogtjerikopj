<?php

namespace NaturaSiberica\Api\V2\Controllers\Search;

use NaturaSiberica\Api\Interfaces\Controllers\Search\SearchControllerInterface;
use NaturaSiberica\Api\Interfaces\Services\Search\SearchServiceInterface;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use NaturaSiberica\Api\V2\Services\Search\SearchService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SearchController implements SearchControllerInterface
{
    use ResponseResultTrait;

    private SearchServiceInterface $service;

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
