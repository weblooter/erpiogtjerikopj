<?php

namespace NaturaSiberica\Api\Interfaces\Repositories\Sale;

interface DeliveryRepositoryInterface
{
    const DELIVERY_XML_ID_COURIER = 'courier';
    const DELIVERY_XML_ID_PICKUP = 'pickup';


    const DELIVERY_TYPE_CODE_ORDER_PICKUP_POINT = 'PVZ';
    const DELIVERY_TYPE_CODE_COURIER            = 'CUR';
    const DELIVERY_TYPE_CODE_POST               = 'POST';

    /**
     * @param int $fuserId
     * @param int $userId
     * @param int $cityId
     *
     * @return array
     */
    public function getDeliveries(int $fuserId, int $userId, int $cityId): array;
}
