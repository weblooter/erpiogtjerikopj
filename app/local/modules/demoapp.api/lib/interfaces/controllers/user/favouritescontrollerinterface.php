<?php

namespace NaturaSiberica\Api\Interfaces\Controllers\User;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface FavouritesControllerInterface
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    public function getByUid(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    public function addProduct(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    public function deleteProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface;

    public function clear(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
