<?php

namespace NaturaSiberica\Api\V2\Services\Catalog;

use Exception;
use NaturaSiberica\Api\Helpers\Catalog\ProductsHelper;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Interfaces\Services\Catalog\ProductServiceInterface;
use NaturaSiberica\Api\Services\Catalog\ProductService as ProductServiceV1;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class ProductService implements ProductServiceInterface, ConstantEntityInterface
{
    use InfoBlockTrait;

    private ProductServiceV1 $productService;

    public function __construct()
    {
        $this->productService = new ProductServiceV1();
    }

    /**
     * Получает информацию об одном элементе
     *
     * @param string $code
     * @param array  $params
     *
     * @return array
     *
     * @throws Exception
     */
    public function get(string $code, array $params): array
    {
        $product = $this->productService->get($code, $params);
        ProductsHelper::prepareDetailImagesForVersion($this->productService->iblockId, $product);
        return $product;
    }

    /**
     * Список товаров
     *
     * @param array $params
     *
     * @return array
     *
     * @throws Exception
     */
    public function index(array $params): array
    {
        $data = $this->productService->index($params);
        ProductsHelper::prepareListImagesForVersion($this->productService->iblockId, $data['list']);
        return $data;
    }
}
