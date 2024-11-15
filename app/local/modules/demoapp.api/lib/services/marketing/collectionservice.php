<?php

namespace NaturaSiberica\Api\Services\Marketing;

use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Marketing\CollectionRepository;
use NaturaSiberica\Api\Services\ParamsServices;
use Bitrix\Main\Config\Option;

class CollectionService
{
    private CollectionRepository $repository;
    protected array              $params = [];

    public function index(array $params = []): array
    {
        $this->init($params);
        $list = $this->repository->all(
            $this->prepareFilter(),
            $this->params['limit'],
            $this->params['offset']
        );
        return [
            'pagination' => [
                'limit'  => count($list),
                'offset' => 0,
                'total'  => $this->repository->count(),
            ],
            'list'       => $list,
        ];
    }

    protected function init(array $params): void
    {
        $this->params     = $this->prepareParams($params);
        $postfix          = ($this->params['lang'] !== 'ru' ? '_' . $this->params['lang'] : '');
        $this->repository = new CollectionRepository(ConstantEntityInterface::IBLOCK_COLLECTION . $postfix);
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

        if (key_exists('codes', $params) && $params['codes']) {
            $params['codes'] = $paramService->prepareListParams('codes', $params['codes']);
        }

        if (key_exists('ids', $params) && $params['ids']) {
            $params['ids'] = $paramService->prepareListParams('ids', $params['ids']);
        }

        return $params;
    }

    protected function prepareFilter(): array
    {
        $filter = [];

        if ($this->params['ids']) {
            $filter['ID'] = $this->params['ids'];
        }

        if ($this->params['codes']) {
            $filter['CODE'] = $this->params['codes'];
        }

        return $filter;
    }

}
