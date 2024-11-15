<?php

namespace NaturaSiberica\Api\Interfaces\Services\Sale;

use NaturaSiberica\Api\Interfaces\Services\ServiceInterface;

interface CartServiceInterface extends ServiceInterface
{
    /**
     * Получение корзины
     *
     * @param int $fuserId
     *
     * @return array
     */
    public function index(int $fuserId): array;

    /**
     * Обновление корзины
     *
     * @param int   $fuserId
     * @param array $products
     *
     * @return array
     */
    public function update(int $fuserId, array $productItems): array;

    /**
     * Удаление корзины
     *
     * @param int $fuserId
     *
     * @return array
     */
    public function delete(int $fuserId): array;
}
