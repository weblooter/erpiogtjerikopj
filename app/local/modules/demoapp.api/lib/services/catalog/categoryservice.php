<?php

namespace NaturaSiberica\Api\Services\Catalog;

use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Catalog\CategoryRepository;
use NaturaSiberica\Api\Services\ParamsServices;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ServerRequestFactory;

class CategoryService implements ConstantEntityInterface
{
    private CategoryRepository $repository;
    protected array $params = [];

    /**
     * @param array $params
     *
     * @return array
     */
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
                'limit' => count($list),
                'offset' => $this->params['offset'],
                'total' => ($this->repository->count() ?: 0)
            ],
            'list' => $list
        ];
    }

    protected function init(array $params): void
    {
        $this->params = $this->prepareParams($params);
        $postfix = ($this->params['lang'] !== 'ru' ? '_' . $this->params['lang'] : '');
        $this->repository = new CategoryRepository(ConstantEntityInterface::IBLOCK_CATALOG.$postfix);
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
