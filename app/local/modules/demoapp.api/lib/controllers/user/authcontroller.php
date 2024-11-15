<?php

namespace NaturaSiberica\Api\Controllers\User;

use Exception;
use NaturaSiberica\Api\Interfaces\Services\User\AuthServiceInterface;
use NaturaSiberica\Api\Services\User\AuthService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use NaturaSiberica\Api\Controllers\AbstractController;
use NaturaSiberica\Api\Interfaces\Controllers\User\AuthControllerInterface;
use Slim\Psr7\Response;

class AuthController implements AuthControllerInterface
{
    use RequestServiceTrait, ResponseResultTrait;

    private AuthServiceInterface $authService;

    public function __construct()
    {
        $this->setAuthService(new AuthService());
    }

    /**
     * @param AuthServiceInterface $authService
     */
    private function setAuthService(AuthServiceInterface $authService): void
    {
        $this->authService = $authService;
    }

    /**
     * @inheritDoc
     */
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->authService, 'register'],
            [$request->getAttribute('fuserId'), $this->parseRequestBody($request)]
        );
    }

    /**
     * @inheritDoc
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->authService, 'login'],
            [$request->getAttribute('fuserId'), $this->parseRequestBody($request)]
        );
    }

    /**
     * @inheritDoc
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->authService, 'logout'],
            [$request->getAttribute('userId'), $request->getHeader('Authorization')]
        );
    }


}
