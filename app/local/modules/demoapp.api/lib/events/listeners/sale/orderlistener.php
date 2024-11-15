<?php

namespace NaturaSiberica\Api\Events\Listeners\Sale;

use Bitrix\Main\Event;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use NaturaSiberica\Api\Events\Handlers\Sale\OrderHandler;
use NaturaSiberica\Api\Events\Listeners\Listener;

class OrderListener extends Listener
{
    private static function getHandler(): OrderHandler
    {
        return new OrderHandler();
    }
    /**
     * @param int        $orderId
     * @param int|string $statusId
     *
     * @return bool
     */
    public static function OnSaleStatusOrder(int $orderId, $statusId): bool
    {
        return self::getHandler()->updateOrderStatus($orderId, $statusId);
    }
}
