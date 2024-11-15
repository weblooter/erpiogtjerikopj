<?php

namespace NaturaSiberica\Api\Services\Marketing;

use CLang;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Marketing\BannerRepository;
use NaturaSiberica\Api\Services\ParamsServices;
use Bitrix\Main\Type\DateTime;

class BannerService
{
    protected BannerRepository $repository;
    protected array $params = [];

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
                'limit' => count($list),
                'offset' => $this->params['offset'],
                'total' => $this->repository->count()
            ],
            'list' => $list
        ];
    }

    protected function init(array $params): void
    {
        $this->params = $this->prepareParams($params);
        $postfix = ($this->params['lang'] !== 'ru' ? '_' . $this->params['lang'] : '');
        $this->repository = new BannerRepository(ConstantEntityInterface::IBLOCK_BANNER.$postfix);
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
            $params['offset'] = ConstantEntityInterface::MIN_OFFSET_VALUE;
        }

        return $params;
    }

    protected function prepareFilter(): array
    {
        $dateTime = DateTime::createFromPhp((new \DateTime()))->toString();
        return [
            '=ACTIVE' => 'Y',
            [
                'LOGIC' => 'OR',
                [
                    '<=ACTIVE_FROM' => $dateTime,
                    '>=ACTIVE_TO' => $dateTime,
                ],
                [
                    '<=ACTIVE_FROM' => $dateTime,
                    '=ACTIVE_TO' => false,
                ],
                [
                    '=ACTIVE_FROM' => false,
                    '=ACTIVE_TO' => false,
                ]
            ]
        ];
    }
}
