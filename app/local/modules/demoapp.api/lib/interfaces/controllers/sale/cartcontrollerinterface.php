<?php

namespace NaturaSiberica\Api\Interfaces\Controllers\Sale;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface CartControllerInterface
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    public function delete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
