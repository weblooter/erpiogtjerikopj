<?php

namespace NaturaSiberica\Api\Services\Marketing;

use CLang;
use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Marketing\SaleActionRepository;
use NaturaSiberica\Api\Services\ParamsServices;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ServerRequestFactory;

class SaleActionService
{
    protected array              $params = [];
    private SaleActionRepository $repository;

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
                'offset' => $this->params['offset'],
                'total'  => ($this->repository->count() ? : 0),
            ],
            'list'       => $list,
        ];
    }

    protected function init(array $params): void
    {
        $this->params     = $this->prepareParams($params);
        $postfix          = ($this->params['lang'] !== 'ru' ? '_' . $this->params['lang'] : '');
        $this->repository = new SaleActionRepository(ConstantEntityInterface::IBLOCK_ACTION . $postfix);
    }

    protected function prepareParams(array $params): array
    {
        $paramService = new ParamsServices();

        if (key_exists('city', $params)) {
            $params['city'] = $paramService->prepareIntParam('city', $params['city'], 1);
        } else {
            $params['city'] = ConstantEntityInterface::DEFAULT_CITY_ID;
        }

        if (key_exists('lang', $params) && $params['lang']) {
            $params['lang'] = $paramService->prepareStringParams('lang', $params['lang'], 2);
        } else {
            $params['lang'] = ConstantEntityInterface::DEFAULT_LANG_CODE;
        }

        if (key_exists('limit', $params)) {
            $params['limit'] = $paramService->prepareIntParam('limit', $params['limit'], 1);
        } else {
            $params['limit'] = ConstantEntityInterface::DEFAULT_ELEMENT_COUNT;
        }

        if (key_exists('offset', $params)) {
            $params['offset'] = $paramService->prepareIntParam('offset', $params['offset'], 0);
        } else {
            $params['offset'] = 0;
        }

        if (key_exists('ids', $params) && $params['ids']) {
            $params['ids'] = $paramService->prepareListParams('ids', $params['ids']);
        }

        if (key_exists('codes', $params) && $params['codes']) {
            $params['codes'] = $paramService->prepareListParams('codes', $params['codes']);
        }

        return $params;
    }

    protected function prepareFilter(): array
    {
        global $DB;
        $result = [
            '=ACTIVE' => 'Y',
            [
                'LOGIC' => 'OR',
                [
                    '<=ACTIVE_FROM' => date($DB->DateFormatToPHP(CLang::GetDateFormat("FULL")), time()),
                    '>=ACTIVE_TO'   => date($DB->DateFormatToPHP(CLang::GetDateFormat("FULL")), time()),
                ],
                [
                    '<=ACTIVE_FROM' => date($DB->DateFormatToPHP(CLang::GetDateFormat("FULL")), time()),
                    '=ACTIVE_TO'    => false,
                ],
                [
                    '=ACTIVE_FROM' => false,
                    '=ACTIVE_TO'   => false,
                ],
            ],
        ];
        if ($this->params['ids']) {
            $result['ID'] = $this->params['ids'];
        }

        if ($this->params['codes']) {
            $result['CODE'] = $this->params['codes'];
        }

        return $result;
    }
}
