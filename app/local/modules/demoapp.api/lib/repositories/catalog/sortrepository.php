<?php

namespace NaturaSiberica\Api\Repositories\Catalog;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SortRepository
{
    private array $params = [];
    /**
     * @param array $params
     *
     * @return array[]
     */
    public function all(array $params): array
    {
        $this->init($params);
        $data = $this->getData();
        return array_values($data);
    }

    public function getData(): array
    {
        if ($this->params['page'] === 'marketing') {
            return $this->getMarketingData();
        }
        $data = $this->getCatalogData();
        if($this->params['page'] !== 'search') {
            unset($data[0]);
        }
        return $data;
    }

    /**
     * Все возможные значения для сортировки.
     *
     * @return array
     */
    public function getPossibleValues(): array
    {
        $allData = array_merge(
            $this->getCatalogData(),
            $this->getMarketingData()
        );

        return array_column($allData, 'sortBy');
    }

    /**
     * Список сортировок для листинга в личном кабинете маркетолога.
     *
     * @return array[]
     */
    public function getMarketingData(): array
    {
        return [
            ['name' => Loc::getMessage('brand'), 'code' => 'brand', 'sortBy' => 'brand', 'sortOrder' => 'asc'],
            ['name' => Loc::getMessage('product_type'), 'code' => 'productType', 'sortBy' => 'productType', 'sortOrder' => 'asc']
        ];
    }

    /**
     * Список сортировок для листинга в каталоге.
     *
     * @return array[]
     */
    public function getCatalogData(): array
    {
        return [
            ['name' => Loc::getMessage('default'), 'code' => 'default', 'sortBy' => 'default', 'sortOrder' => 'asc'],
            ['name' => Loc::getMessage('most_popular'), 'code' => 'popular', 'sortBy' => 'popular', 'sortOrder' => 'desc'],
            ['name' => Loc::getMessage('low_price'), 'code' => 'price', 'sortBy' => 'price', 'sortOrder' => 'asc'],
            ['name' => Loc::getMessage('high_price'), 'code' => 'price', 'sortBy' => 'price', 'sortOrder' => 'desc'],
            ['name' => Loc::getMessage('new'), 'code' => 'news', 'sortBy' => 'new_id', 'sortOrder' => 'desc'],
            //            ['name' => 'по размеру наибольшей скидки', 'code' => 'discount', 'sortBy' => 'discount', 'sortOrder' => 'asc'],
        ];
    }

    private function init(array $params)
    {
        $this->params = $params;
    }
}
