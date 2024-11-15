<?php

namespace NaturaSiberica\Api\Controllers\Sale;

use NaturaSiberica\Api\Controllers\AbstractController;
use NaturaSiberica\Api\Interfaces\Controllers\Sale\OrderControllerInterface;
use NaturaSiberica\Api\Interfaces\Services\Sale\OrderServiceInterface;
use NaturaSiberica\Api\Services\Sale\OrderService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OrderController implements OrderControllerInterface
{
    use RequestServiceTrait, ResponseResultTrait;

    protected OrderServiceInterface $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    public function checkCart(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->orderService, 'checkCart'],
            [$request->getAttribute('fuserId')]
        );
    }

    /**
     * @inheritDoc
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        return $this->prepareResponse($response, [$this->orderService, 'show'], [
            $request->getAttribute('fuserId'),
            $request->getAttribute('userId'),
            $args['id'],
            (bool)$queryParams['archive'],
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     * @deprecated Пока не используется
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse($response, [$this->orderService, 'create'], [
            $request->getAttribute('fuserId'),
            $request->getAttribute('userId'),
            $this->parseRequestBody($request),
        ]);
    }

    public function cancel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->prepareResponse($response, [$this->orderService, 'cancel'], [
            $request->getAttribute('fuserId'),
            $request->getAttribute('userId'),
            $args['id'],
        ]);
    }

    public function getStatuses(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse($response, [$this->orderService, 'getStatuses'], [$request->getQueryParams()]);
    }

    public function getPayments(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        return $this->prepareResponse($response, [$this->orderService, 'getPayments'], [
            $request->getAttribute('fuserId'),
            $request->getAttribute('userId'),
            $queryParams['deliveryId'],
        ]);
    }

    public function getPaymentUrl(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->prepareResponse($response, [
            $this->orderService,
            'getPaymentUrl',
        ], [
            $args['id'],
            $request->getAttribute('fuserId'),
            $request->getAttribute('userId'),

        ]);
    }

    public function getDeliveries(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        return $this->prepareResponse($response, [$this->orderService, 'getDeliveries'], [
            $request->getAttribute('fuserId'),
            $request->getAttribute('userId'),
            $queryParams['city'],
        ]);
    }

    public function getFreeShipping(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse($response, [$this->orderService, 'getFreeShipping'], [
            $request->getAttribute('fuserId'),
            $request->getAttribute('userId')
        ]);
    }


}
