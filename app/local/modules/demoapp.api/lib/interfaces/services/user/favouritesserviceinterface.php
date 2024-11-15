<?php

namespace NaturaSiberica\Api\Interfaces\Services\User;

interface FavouritesServiceInterface
{
    public function index(int $userId): array;

    public function getByUid(string $uid): array;

    public function addProduct(int $userId, array $products): array;

    public function deleteProduct(int $userId, int $productId): array;

    public function clear(int $userId): array;
}
