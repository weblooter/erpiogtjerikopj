<?php

namespace NaturaSiberica\Api\Interfaces\Controllers\User\Favourites;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface SharedLinksControllerInterface
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
