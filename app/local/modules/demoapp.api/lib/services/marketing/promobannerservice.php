<?php

namespace NaturaSiberica\Api\Services\Marketing;

use Bitrix\Main\Type\DateTime;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Marketing\PromoBannerRepository;
use NaturaSiberica\Api\Services\ParamsServices;

class PromoBannerService
{

    protected PromoBannerRepository $repository;
    protected int $defaultLimit = 3;
    protected array $params = [];

    public function index(array $params = []): array
    {
        $this->init($params);
        $list = $this->repository->all(
            $this->prepareFilter(),
            $this->defaultLimit
        );
        return ['list' => $list];
    }

    protected function init(array $params): void
    {
        $this->params = $this->prepareParams($params);
        $postfix = ($this->params['lang'] !== 'ru' ? '_' . $this->params['lang'] : '');
        $this->repository = new PromoBannerRepository(ConstantEntityInterface::IBLOCK_PROMO_BANNER.$postfix);
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

        if (key_exists('ids', $params) && $params['ids']) {
            $params['ids'] = $paramService->prepareListParams('ids', $params['ids']);
        }

        return $params;
    }

    protected function prepareFilter(): array
    {
        $dateTime = DateTime::createFromPhp((new \DateTime()))->toString();
        $filter = [
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
        if($this->params['ids']) {
            $filter['ID'] = $this->params['ids'];
        }
        return $filter;
    }
}
