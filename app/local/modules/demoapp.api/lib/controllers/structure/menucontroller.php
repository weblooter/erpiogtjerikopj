<?php

namespace NaturaSiberica\Api\Controllers\Structure;

use NaturaSiberica\Api\Services\Structure\MenuService;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MenuController
{
    use ResponseResultTrait;

    private MenuService $menuService;

    public function __construct()
    {
        $this->menuService = new MenuService();
    }

    public function getHeaderMenu(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->prepareResponse(
            $response,
            [$this->menuService, 'getHeaderMenu'],
            [],
            $request
        );
    }

    public function getFooterMenu(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->prepareResponse(
            $response,
            [$this->menuService, 'getFooterMenu'],
            [],
            $request
        );
    }
}
