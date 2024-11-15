<?php

namespace NaturaSiberica\Api\Controllers\Content\Pages;

use NaturaSiberica\Api\Services\Content\Pages\PageService;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PagesController
{
    use ResponseResultTrait;

    private PageService $pageService;

    public function __construct()
    {
        $this->pageService = new PageService();
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        return $this->prepareResponse(
            $response,
            [$this->pageService, 'getPage'],
            [$args['code'], $request->getQueryParams()],
            $request
        );
    }
}
