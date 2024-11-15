<?php

namespace NaturaSiberica\Api\V2\Services\Search;

use NaturaSiberica\Api\Helpers\Catalog\ProductsHelper;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Interfaces\Services\Search\SearchServiceInterface;
use NaturaSiberica\Api\Services\Search\SearchService as V1SearchService;

class SearchService implements SearchServiceInterface
{
    private V1SearchService $service;

    public function __construct()
    {
        $this->service = new V1SearchService();
    }

    public function index(array $params): array
    {
        $iblockId = $this->service->getIblockId(ConstantEntityInterface::IBLOCK_CATALOG);
        $data = $this->service->index($params);

        ProductsHelper::prepareListImagesForVersion($iblockId, $data['productList']['list']);
        return $data;
    }
}
