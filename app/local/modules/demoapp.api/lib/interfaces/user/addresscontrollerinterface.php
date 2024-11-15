<?php

namespace NaturaSiberica\Api\Interfaces\User;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface AddressControllerInterface
{
    public function getAddress(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface;

    public function addAddress(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    public function editAddress(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface;

    public function deleteAddress(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface;
}
