<?php

namespace NaturaSiberica\Api\Controllers\Catalog;

use NaturaSiberica\Api\Interfaces\Controllers\Catalog\OfferControllerInterface;
use NaturaSiberica\Api\Services\Catalog\OfferService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OfferController implements OfferControllerInterface
{
    use RequestServiceTrait, ResponseResultTrait;

    private OfferService $service;

    public function __construct()
    {
        $this->service = new OfferService();
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->service, 'index'],
            [$request->getQueryParams(), 'show_detail', false],
            $request
        );
    }

}
