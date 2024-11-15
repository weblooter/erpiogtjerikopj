<?php
namespace NaturaSiberica\Api\Interfaces\Controllers\Catalog;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface OfferControllerInterface
{

    /**
     * Карточка торгового предложения
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function get(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
