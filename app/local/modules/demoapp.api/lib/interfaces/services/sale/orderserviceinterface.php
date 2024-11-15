<?php

namespace NaturaSiberica\Api\Interfaces\Services\Sale;

interface OrderServiceInterface
{
    public function show(int $fuserId, int $userId, int $id = null, bool $archive = false): array;

    public function checkCart(int $fuserId): array;

    public function create(int $fuserId, int $userId, array $body): array;

    public function cancel(int $fuserId, int $userId, int $id): array;

    public function getPayments(int $fuserId, int $userId, int $deliveryId): array;

    public function getPaymentUrl(int $orderId, int $fuserId, int $userId): array;

    public function getDeliveries(int $fuserId, ?int $userId, int $cityId): array;

    public function getFreeShipping(int $fuserId, ?int $userId): array;
}
