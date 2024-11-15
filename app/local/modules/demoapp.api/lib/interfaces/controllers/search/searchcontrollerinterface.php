<?php

namespace NaturaSiberica\Api\Interfaces\Controllers\Search;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface SearchControllerInterface
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
