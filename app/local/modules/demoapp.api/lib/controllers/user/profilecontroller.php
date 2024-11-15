<?php

namespace NaturaSiberica\Api\Controllers\User;

use NaturaSiberica\Api\Services\User\ProfileService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use NaturaSiberica\Api\Controllers\AbstractController;
use NaturaSiberica\Api\Interfaces\Controllers\User\AddressControllerInterface;
use NaturaSiberica\Api\Interfaces\Controllers\User\ProfileControllerInterface;
use Slim\Psr7\Response;

class ProfileController implements ProfileControllerInterface, AddressControllerInterface
{
    use RequestServiceTrait, ResponseResultTrait;

    private ProfileService $profileService;

    public function __construct()
    {
        $this->profileService = new ProfileService();
    }

    /**
     * @inheritDoc
     */
    public function getProfile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->profileService, 'getProfile'],
            [$request->getAttribute('userId')]
        );
    }

    /**
     * @inheritDoc
     */
    public function editProfile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->profileService, 'editProfile'],
            [$request->getAttribute('userId'), $this->parseRequestBody($request)]
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function clearProfile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->profileService, 'clearProfile'],
            [$request->getAttribute('userId')]
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param array                  $args
     *
     * @return ResponseInterface
     */
    public function getAddress(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->profileService, 'getAddress'],
            [$request->getAttribute('userId'), $args['id']]
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function addAddress(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->profileService, 'addAddress'],
            [$request->getAttribute('userId'), $this->parseRequestBody($request)]
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param array                  $args
     *
     * @return ResponseInterface
     */
    public function editAddress(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->profileService, 'editAddress'],
            [$request->getAttribute('userId'), $this->parseRequestBody($request), $args['id']]
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param array                  $args
     *
     * @return ResponseInterface
     */
    public function deleteAddress(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->profileService, 'deleteAddress'],
            [$request->getAttribute('userId'), $args['id']]
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function editNotifications(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->profileService, 'editNotifications'],
            [$request->getAttribute('userId'), $this->parseRequestBody($request)]
        );
    }

    public function changeEmail(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->profileService, 'changeEmail'],
            [$request->getAttribute('userId'), $this->parseRequestBody($request)]
        );
    }
}
