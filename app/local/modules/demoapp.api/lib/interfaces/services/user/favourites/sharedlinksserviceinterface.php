<?php

namespace NaturaSiberica\Api\Interfaces\Services\User\Favourites;

interface SharedLinksServiceInterface
{
    public function index(int $userId): array;
}
