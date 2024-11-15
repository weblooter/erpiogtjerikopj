<?php

namespace NaturaSiberica\Api\Controllers\Content;

use NaturaSiberica\Api\Services\Content\NewsService;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NewsController
{
    use ResponseResultTrait;

    private NewsService $service;

    public function __construct()
    {
        $this->service = new NewsService();
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->service, 'index'],
            [$request->getQueryParams()],
            $request
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function get(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->service, 'get'],
            [$request->getAttribute('code'), $request->getQueryParams()],
            $request
        );
    }
}
