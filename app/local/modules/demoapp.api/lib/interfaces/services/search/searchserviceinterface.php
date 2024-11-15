<?php

namespace NaturaSiberica\Api\Interfaces\Services\Search;

interface SearchServiceInterface
{
    public function index(array $params): array;
}
