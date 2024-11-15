<?php

namespace NaturaSiberica\Api\Interfaces\User;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ProfileControllerInterface
{
    /**
     * Получение данных профиля пользователя
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function getProfile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    /**
     * Редактирование профиля пользователя
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function editProfile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;


}
