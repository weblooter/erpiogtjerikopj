<?php

namespace NaturaSiberica\Api\Services\Catalog;

use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Catalog\FastFilterRepository;
use NaturaSiberica\Api\Services\ParamsServices;

class FastFilterService implements ConstantEntityInterface
{
    private FastFilterRepository $repository;
    protected array $params = [];

    /**
     * @param array $params
     *
     * @return array
     */
    public function index(array $params): array
    {
        $this->init($params);
        $query = $this->repository->setQuery()->setFilter($this->prepareFilter());
        if($this->params['fastUrl']) {
            $query = $query->setLimit(1);
        }
        $list = $query->all();
        return [
            'list' => $list
        ];
    }

    protected function init(array $params): void
    {
        $this->params = $this->prepareParams($params);
        $this->repository = new FastFilterRepository();
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

        if (key_exists('categoryUrl', $params) && $params['categoryUrl']) {
            $params['categoryUrl'] = $paramService->prepareStringParams('categoryUrl', $params['categoryUrl'], ConstantEntityInterface::MIN_FAST_FILTER_LENGTH);
        }

        if (key_exists('fastUrl', $params) && $params['fastUrl']) {
            $params['fastUrl'] = $paramService->prepareStringParams('fastUrl', $params['fastUrl'], ConstantEntityInterface::MIN_FAST_FILTER_LENGTH);
        }

        return $params;
    }

    protected function prepareFilter(): array
    {
        $filter = [];
        if($this->params['categoryUrl']) {
            $filter['categoryUrl'] = $this->params['categoryUrl'];
        }
        if($this->params['fastUrl']) {
            $filter['fastUrl'] = $this->params['fastUrl'];
        }
        return $filter;
    }

}
