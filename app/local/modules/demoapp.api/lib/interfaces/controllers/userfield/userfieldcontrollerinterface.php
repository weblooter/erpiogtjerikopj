<?php

namespace NaturaSiberica\Api\Interfaces\Controllers\UserField;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface UserFieldControllerInterface
{
    public function getSkinTypes(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    public function getMaritalStatuses(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
