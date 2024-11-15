<?php

namespace NaturaSiberica\Api\Services\Mindbox;

use NaturaSiberica\Api\Mindbox\MindboxRepository;

class CheckCustomerService
{
    protected MindboxRepository $repository;
    protected string $operation = 'Website.CheckCustomer';
    protected array $params = [];

    public function __construct()
    {
        $this->repository = new MindboxRepository();
    }

    public function index(array $body): array
    {
        $data = $this->repository->getExport($this->operation, $body);
        if($data['status'] === 'Success') {
            return $data;
        }
        return [];
    }
}
