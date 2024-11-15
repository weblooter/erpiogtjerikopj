<?php

namespace NaturaSiberica\Api\Interfaces\Repositories\Sale;

use Bitrix\Sale\Order;

/**
 * Интерфейс для получения списка способов оплаты/доставки
 */
interface OrderServicesRepositoryInterface
{
    /**
     * @param int $userId
     *
     * @return mixed
     */
    public function list(int $userId);

    /**
     * @param Order $order
     * @param bool  $toArray
     *
     * @return mixed
     */
    public function prepareItems(Order $order, bool $toArray = true);
}
