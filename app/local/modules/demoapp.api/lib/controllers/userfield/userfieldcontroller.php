<?php

namespace NaturaSiberica\Api\Controllers\UserField;

use NaturaSiberica\Api\Interfaces\Controllers\UserField\UserFieldControllerInterface;
use NaturaSiberica\Api\Interfaces\Services\UserField\UserFieldServiceInterface;
use NaturaSiberica\Api\Services\UserField\UserFieldService;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserFieldController implements UserFieldControllerInterface
{
    use ResponseResultTrait;

    private UserFieldServiceInterface $userFieldService;

    public function __construct()
    {
        $this->userFieldService = new UserFieldService();
    }

    public function getSkinTypes(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->userFieldService, 'getSkinTypes']
        );
    }

    public function getMaritalStatuses(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->userFieldService, 'getMaritalStatuses']
        );
    }
}
