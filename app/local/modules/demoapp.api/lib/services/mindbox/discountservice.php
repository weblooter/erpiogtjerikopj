<?php

namespace NaturaSiberica\Api\Services\Mindbox;

use NaturaSiberica\Api\Mindbox\MindboxRepository;

class DiscountService
{
    protected MindboxRepository $repository;
    protected string $operation = 'Website.CalculatePriceProduct';
    protected array $params = [];

    public function __construct()
    {
        $this->repository = new MindboxRepository();
    }

    public function index(array $body): array
    {
        if(!$body['customer'] || !$body['customer']['mobilePhone']) {
            $this->operation = $this->operation.'ForAnonym';
        }
        $data = $this->repository->getExport($this->operation, $body);
        if($data['status'] === 'Success' && $data['productList']) {
            return $data['productList'];
        }
        return [];
    }
}
