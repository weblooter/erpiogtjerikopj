<?php

namespace NaturaSiberica\Api\Interfaces\Repositories\Sale;

use Spatie\DataTransferObject\DataTransferObject;

interface PaymentsRepositoryInterface
{
    public function getPayments(int $fuserId, int $deliveryId): array;

    public function findById(int $fuserId,int $paySystemId): PaymentsRepositoryInterface;

    public function all(): array;

    public function get(): ?DataTransferObject;
}
