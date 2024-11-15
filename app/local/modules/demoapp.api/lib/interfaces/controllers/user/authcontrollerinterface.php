<?php

namespace NaturaSiberica\Api\Interfaces\Controllers\User;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Интерфейс для обработки маршрутов регистрации/авторизации
 */
interface AuthControllerInterface
{
    /**
     * Регистрация нового пользователя
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    /**
     * Авторизация пользователя
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    /**
     * Разлогинивание пользователя
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
