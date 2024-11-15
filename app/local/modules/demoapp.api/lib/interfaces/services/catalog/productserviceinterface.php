<?php

namespace NaturaSiberica\Api\Interfaces\Services\Catalog;

interface ProductServiceInterface
{
    /**
     * Список товаров
     *
     * @param array $params
     *
     * @return array
     */
    public function index(array $params): array;

    /**
     * Информация о товаре
     *
     * @param string $code
     * @param array  $params
     *
     * @return array
     */
    public function get(string $code, array $params): array;
}
