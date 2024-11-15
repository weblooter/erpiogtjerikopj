<?php
namespace NaturaSiberica\Api\Services\Marketing;

use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Helpers\Catalog\ProductsHelper;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Marketing\BrandRepository;
use NaturaSiberica\Api\Services\ParamsServices;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ServerRequestFactory;

class BrandService
{
    protected BrandRepository $repository;
    protected array $params = [];

    public function index(array $params): array
    {
        $this->init($params);
        $list = $this->repository->all(
            $this->prepareFilter(),
            $this->params['limit'],
            $this->params['offset']
        );

        return [
            'pagination' => [
                'limit' => $this->params['limit'],
                'offset' => $this->params['offset'],
                'total' => $this->repository->count()
            ],
            'list' => $list
        ];
    }

    public function getMainBrandProducts(array $params): array
    {
        $this->init($params);
        if(!$this->params['id']) {
            throw new RequestBodyException('Нет id бренда');
        }
        $this->params['filter'] = json_encode([
            'brandId' => $this->params['id'],
            'sort_for_brand' => ['from' => 0, 'to' => $this->params['limit']]
        ]);
        $this->params['sort'] = 'brand_score';
        unset($this->params['id']);
        $productService = new \NaturaSiberica\Api\Services\Catalog\ProductService();
        $products = $productService->index($this->params);

        return ($products['list'] ? ProductsHelper::prepareListImagesForVersion($productService->iblockId, $products['list']) : []);
    }

    protected function init(array $params): void
    {
        $this->params = $this->prepareParams($params);
        $postfix = ($this->params['lang'] !== 'ru' ? '_' . $this->params['lang'] : '');
        $this->repository = new BrandRepository(ConstantEntityInterface::IBLOCK_BRAND.$postfix);
    }

    protected function prepareParams(array $params): array
    {
        $paramService = new ParamsServices();

        if (key_exists('city', $params)) {
            $params['city'] = $paramService->prepareIntParam('city', $params['city'], ConstantEntityInterface::MIN_CITY_VALUE);
        } else {
            $params['city'] = ConstantEntityInterface::DEFAULT_CITY_ID;
        }

        if (key_exists('lang', $params) && $params['lang']) {
            $params['lang'] = $paramService->prepareStringParams('lang', $params['lang'], ConstantEntityInterface::MIN_LANG_LENGTH);
        } else {
            $params['lang'] = ConstantEntityInterface::DEFAULT_LANG_CODE;
        }

        if (key_exists('limit', $params)) {
            $params['limit'] = $paramService->prepareIntParam('limit', $params['limit'], ConstantEntityInterface::MIN_LIMIT_VALUE);
        } else {
            $params['limit'] = ConstantEntityInterface::DEFAULT_ELEMENT_COUNT;
        }

        if (key_exists('offset', $params)) {
            $params['offset'] = $paramService->prepareIntParam('offset', $params['offset'], ConstantEntityInterface::MIN_OFFSET_VALUE);
        } else {
            $params['offset'] = 0;
        }

        if (key_exists('ids', $params) && $params['ids']) {
            $params['ids'] = $paramService->prepareListParams('ids', $params['ids']);
        }

        if (key_exists('id', $params) && $params['id']) {
            $params['id'] = $paramService->prepareIntParam('id', $params['id'], 1);
        }

        return $params;
    }

    protected function prepareFilter(): array
    {
        if($this->params['ids']) {
            return ['ID' => $this->params['ids']];
        }
        return [];
    }
}
