<?php

namespace NaturaSiberica\Api\Interfaces\Services\User;

interface PhoneServiceInterface
{
    public function generateCode(array $body, int $fuserId, int $userId = null): array;

    public function confirm(array $body, int $fuserId, int $userId = null): array;

    public function createCode(string $phoneNumber);

    public function verifyCode(string $phoneNumber, string $code, string $field): bool;
}
