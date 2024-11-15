<?php

namespace NaturaSiberica\Api\Services\Catalog;

use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Catalog\SortRepository;
use NaturaSiberica\Api\Services\ParamsServices;

class SortService
{
    private SortRepository $repository;
    protected array $params = [];

    public function index(array $params): array
    {
        $this->init($params);
        $list = $this->repository->all($this->params);
        return ['list' => $list];
    }
    protected function init(array $params): void
    {
        $this->params = $this->prepareParams($params);
        $this->repository = new SortRepository();
    }

    protected function prepareParams(array $params): array
    {
        $paramService = new ParamsServices();

        if (key_exists('city', $params)) {
            $params['city'] = $paramService->prepareIntParam('city', $params['city']);
        } else {
            $params['city'] = ConstantEntityInterface::DEFAULT_CITY_ID;
        }

        if (key_exists('lang', $params) && $params['lang']) {
            $params['lang'] = $paramService->prepareStringParams('lang', $params['lang'], 2);
        } else {
            $params['lang'] = ConstantEntityInterface::DEFAULT_LANG_CODE;
        }

        return $params;
    }

}
