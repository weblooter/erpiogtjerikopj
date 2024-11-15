<?php

namespace NaturaSiberica\Api\Repositories\Catalog;

use Bitrix\Catalog\GroupTable;
use \Bitrix\Catalog\Model\Price;
use Bitrix\Main\Loader;

Loader::includeModule("catalog");

class PricesRepository
{
    protected int $type;

    public function __construct()
    {
        $this->type = $this->getPriceType();
    }

    /**
     * Получает id базового типа цены
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getPriceType(): int
    {
        $data = GroupTable::getList([
            'filter' => ['BASE' => 'Y'],
            'select' => ['ID']
        ])->fetch();
        return $data['ID'];
    }

    /**
     * Получает массив цен товаров в копейках
     * @param array $ids
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getPriceList(array $ids = []): array
    {
        $filter['CATALOG_GROUP_ID'] =  $this->type;
        if($ids) {
            $filter['PRODUCT_ID'] =  $ids;
        }
        $data = Price::getList([
            'filter' => $filter,
            'select' => ['PRICE', 'PRODUCT_ID']
        ]);
        $result = [];
        while ($item = $data->fetch()) {
            $result[$item['PRODUCT_ID']] = (int)($item['PRICE'] * 100);
        }
        return $result;
    }
}
