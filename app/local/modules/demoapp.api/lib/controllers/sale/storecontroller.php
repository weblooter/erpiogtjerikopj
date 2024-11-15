<?php

namespace NaturaSiberica\Api\Controllers\Sale;

use NaturaSiberica\Api\Controllers\AbstractController;
use NaturaSiberica\Api\Services\Sale\StoreService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StoreController
{
    use ResponseResultTrait;

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $storeService = new StoreService();
        $queryParams = $request->getQueryParams();
        $cityId = (int) $queryParams['city'];

        return $this->prepareResponse(
            $response,
            [$storeService, 'getStores'],
            [$cityId],
            $request
        );
    }
}
