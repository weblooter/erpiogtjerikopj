<?php

namespace NaturaSiberica\Api\Interfaces\Controllers\Content;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface CertificateControllerInterface
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
