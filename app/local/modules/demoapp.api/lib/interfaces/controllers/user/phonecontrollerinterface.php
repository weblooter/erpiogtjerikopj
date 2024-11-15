<?php

namespace NaturaSiberica\Api\Interfaces\Controllers\User;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface PhoneControllerInterface
{
    /**
     * Подтверждение телефона в процессе регистрации
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function confirm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;

    /**
     * Получение одноразового смс-кода для подтверждения телефона
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     *
     * @return ResponseInterface
     */
    public function getCode(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
