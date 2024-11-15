<?php

namespace NaturaSiberica\Api\Interfaces\Controllers\User;

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

    /**
     * Настройки уведомлений
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function editNotifications(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    public function changeEmail(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
