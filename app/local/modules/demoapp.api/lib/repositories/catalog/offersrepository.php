<?php

namespace NaturaSiberica\Api\Repositories\Catalog;

use Bitrix\Main\Loader;
use NaturaSiberica\Api\Traits\Entities;
use NaturaSiberica\Api\Traits\NormalizerTrait;

Loader::includeModule('iblock');

class OffersRepository
{
    use Entities\InfoBlockTrait, Entities\FileTrait, Entities\HighloadBlockTrait, NormalizerTrait;

    protected ElementPropertyRepository $property;
    protected ElementFieldRepository    $field;
    protected StoresRepository          $store;
    protected PricesRepository          $price;
    protected int                       $iblockId;
    protected array                     $filter       = [];
    protected array                     $propertyList = [];
    protected array                     $select       = ['ID', 'ACTIVE', 'XML_ID', 'CODE', 'NAME', 'SORT', 'DATE_CREATE'];

    public function __construct($code)
    {
        $this->iblockId = $this->getIblockId($code);
        $this->property = new ElementPropertyRepository($this->iblockId);
        $this->field    = new ElementFieldRepository();
        $this->store    = new StoresRepository();
        $this->price    = new PricesRepository();
    }

    /**
     * Получает список товаров
     *
     * @param int $limit  Количесвто запрашиваемых элементов
     * @param int $offset Количество элементов, которые необходимо пропустить
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function get(int $limit = 0, int $offset = 0): array
    {
        $result       = [];
        $ids   = $this->getElementCollection($this->iblockId,['ID'],$this->filter,
            [],
            $limit,$offset
        )->fill('ID');
        $collection   = $this->getElementCollection(
            $this->iblockId,
            $this->getSelectList(),
            ['ID' => $ids]
        );
        $ids = $collection->fill('ID');
        $priceList    = $this->getPrices($ids);
        $availability = $this->getAvailability($ids);
        $seoData  = $this->getMetaData($this->iblockId, 'E', $ids);
        foreach ($collection as $item) {
            $result[] = array_merge(
                $this->getElementFields($item),
                $this->getElementProperties($item),
                ['price' => $priceList[$item->get('ID')]],
                $availability[$item->get('ID')],
                ['seo_data' => ($seoData[$item->get('ID')] ? [
                    'title'       => $seoData[$item->get('ID')]['ELEMENT_META_TITLE'],
                    'description' => $seoData[$item->get('ID')]['ELEMENT_META_DESCRIPTION'],
                    'page_name'   => $seoData[$item->get('ID')]['ELEMENT_PAGE_TITLE'],
                    'keywords'   => $seoData[$item->get('ID')]['ELEMENT_META_KEYWORDS'],
                ] : [])]
            );
        }
        return $result;
    }

    public function getAvailability(array $skuListIds): array
    {
        $result = [];
        $data   = $this->store->getStroesList($skuListIds);
        foreach ($skuListIds as $id) {
            $result[$id]['city_id_list'] = ($data[$id] ? $data[$id]['city_id_list'] : []);
            $result[$id]['warehouses']   = ($data[$id] ? $data[$id]['warehouses'] : []);
            $result[$id]['shops']        = ($data[$id] ? : []);
            unset($result[$id]['shops']['city_id_list'], $result[$id]['shops']['warehouses']);
        }
        return $result;
    }

    /**
     * Добаляет список получаемых полей элемента
     *
     * @param array $params Параметры свойств, которые необходимо получить
     *
     * @return $this
     */
    public function setSelectParams(array $params): OffersRepository
    {
        $this->select = array_merge($this->select, $params);
        return $this;
    }

    /**
     * Добавляет параметры фильтрации элементов
     *
     * @param array $params Параметры фильтрмции свойств инфоблока
     *
     * @return $this
     */
    public function setFilterParams(array $params): OffersRepository
    {
        $this->filter = array_merge($this->select, $params);
        return $this;
    }

    /**
     * Задает список свойств элемента
     *
     * @param array $filter
     * @param array $select
     *
     * @return $this
     */
    public function setPropertyElement(array $filter = [], array $select = []): OffersRepository
    {
        $this->propertyList = $this->property->getList(
            ($filter ? : ['ACTIVE' => 'Y']),
            $select
        );
        return $this;
    }

    /**
     * Получает общее количество элементов
     *
     * @return int
     */
    public function getCount(): int
    {
        try {
            return $this->getElementCollection($this->iblockId, ['ID'])->count();
        } catch (\Exception $e) {
            dump($e);
            return 0;
        }
    }

    /**
     * Получает список запрашиваемых полей и свойств элемента
     *
     * @return array
     */
    protected function getSelectList(): array
    {
        return array_merge($this->select, $this->property->getListNameByType($this->propertyList));
    }

    /**
     * Получает поля элемента
     *
     * @param $item
     *
     * @return array
     */
    protected function getElementFields($item): array
    {
        $result = [];
        foreach ($this->select as $name) {
            $propertyName = $this->convertSnakeToCamel($name, true, true);
            $method       = sprintf('get%s', ucfirst($propertyName));
            if (method_exists($this->field, $method)) {
                $result = array_merge($result, call_user_func([$this->field, $method], $item->get($name)));
            }
        }
        return $result;
    }

    /**
     * Получает свойства элемента
     *
     * @param $item
     *
     * @return array
     */
    protected function getElementProperties($item): array
    {
        $result = [];
        foreach ($this->propertyList as $property) {
            $method = 'getListValueProperty' . $property['PROPERTY_TYPE'] . ucfirst(strtolower($property['USER_TYPE']));
            if (method_exists($this->property, $method)) {
                if ($property['MULTIPLE'] === 'Y') {
                    foreach ($item->get($property['CODE'])->getAll() as $value) {
                        $result[strtolower($property['CODE'])][] = $this->property->$method($value);
                    }
                } else {
                    $result[strtolower($property['CODE'])] = $this->property->$method($item->get($property['CODE']));
                }
            }
        }
        return $result;
    }

    public function getPrices(array $ids = []): array
    {
        return $this->price->getPriceList($ids);
    }

}
