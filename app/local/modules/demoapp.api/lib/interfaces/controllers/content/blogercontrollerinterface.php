<?php

namespace NaturaSiberica\Api\Interfaces\Controllers\Content;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface BlogerControllerInterface
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
