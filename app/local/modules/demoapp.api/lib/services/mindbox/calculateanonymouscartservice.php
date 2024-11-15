<?php

namespace NaturaSiberica\Api\Services\Mindbox;

use NaturaSiberica\Api\Mindbox\MindboxRepository;

class CalculateAnonymousCartService
{
    protected MindboxRepository $repository;
    protected string $operation = 'Website.CalculateAnonymousCart';

    public function __construct()
    {
        $this->repository = new MindboxRepository();
    }

    public function index(array $body): array
    {
        $data = $this->repository->getExport($this->operation, $body);

        return $data['status'] === 'Success' ? ['order' => $data['order']] : [];
    }
}
