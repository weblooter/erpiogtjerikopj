<?php

namespace NaturaSiberica\Api\Interfaces\Services\User;

use NaturaSiberica\Api\Interfaces\Services\ServiceInterface;

interface AuthServiceInterface
{
    const PHONE_CODE = 1234;

    public function register(int $fuserId, array $body);

    public function login(int $fuserId, array $body);

    public function logout(int $userId, $authHeader);
}
