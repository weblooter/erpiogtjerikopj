<?php

namespace NaturaSiberica\Api\Interfaces\Controllers\Sale;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface OrderControllerInterface
{
    public function checkCart(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    /**
     * Просмотр списка заказов / одного заказа
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param array                  $args
     *
     * @return ResponseInterface
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface;

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    public function cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface;

    public function getPayments(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    public function getPaymentUrl(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface;

    public function getDeliveries(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
