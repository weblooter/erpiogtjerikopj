<?php

namespace NaturaSiberica\Api\Repositories\Catalog;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Iblock;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\Join;
use CCatalogSku;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Marketing\SeriesRepository;
use NaturaSiberica\Api\Traits\Entities;
use NaturaSiberica\Api\Traits\NormalizerTrait;
use Bitrix\Main\Type\DateTime;
use CLang;

Loader::includeModule("catalog");

class ProductsRepository
{
    use Entities\InfoBlockTrait, Entities\FileTrait, Entities\HighloadBlockTrait, NormalizerTrait;

    protected ElementPropertyRepository $property;
    protected ElementFieldRepository    $field;
    protected StoresRepository          $stores;
    protected PricesRepository          $prices;
    protected int                       $iblockId;
    protected int                       $skuIblockId;
    protected int                       $limit         = 0;
    protected int                       $offset        = 0;
    protected array                     $propertyList  = [];
    protected array                     $referenceList = [];
    protected array                     $seoData       = [];
    protected array                     $filter        = [];
    protected array                     $select        = [
        'ID',
        'XML_ID',
        'CODE',
        'NAME',
        'SORT',
        'ACTIVE',
        'PREVIEW_PICTURE',
        'PREVIEW_TEXT',
        'DETAIL_PICTURE',
        'DETAIL_TEXT',
        'DATE_CREATE',
        'SECTIONS',
        'SHOW_COUNTER',
        'DISCONTINUED',
    ];

    /**
     * @param string $code Символьный код инфоблока
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function __construct(string $code)
    {
        $this->iblockId    = $this->getIblockId($code);
        $this->skuIblockId = $this->getSkuIblockId($this->iblockId);
        $this->property    = new ElementPropertyRepository($this->iblockId);
        $this->field       = new ElementFieldRepository();
        $this->stores      = new StoresRepository();
        $this->prices      = new PricesRepository();
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
        $result              = [];
        $this->referenceList = (new ElementReferenceRepository())->getReference($this->propertyList);
        $ids                 = $this->getElementIds(['ID' => 'asc'], $limit, $offset);
        $collection          = $this->getElementCollection(
            $this->iblockId, $this->getSelectList(), array_merge($this->filter, ['ID' => $ids]), ['ID' => 'asc']
        );
        $this->field->setImageList(
            $this->getImagePath(
                array_merge(
                    $collection->fill('PREVIEW_PICTURE'),
                    $collection->fill('DETAIL_PICTURE')
                )
            )
        );
        $this->seoData  = $this->getMetaData($this->iblockId, 'E', $ids);
        $skuList        = $this->getSkuList($ids);
        $collectionList = $this->getCollectionList($ids);
        $seriesList     = $this->getSeriesList($collection->fill('SERIES')->fill('ELEMENT')->fill('ID'));
        foreach ($collection as $key => $item) {
            if ($skuList[$item->get('ID')]) {
                $skuIdList = array_column($skuList[$item->get('ID')], 'id');
                $saleList = $this->getSaleActionList($item->get('ID'), $skuIdList);
                $result[$key]           = array_merge(
                    $this->getElementFields($item),
                    ['discontinued' => ($item->get('DISCONTINUED') && $item->get('DISCONTINUED')->getValue())],
                    ['price' => $this->getPrice($skuIdList)],
                    ['sale_id' => ($saleList ?? [])],
                    ['collection_id' => ($collectionList[$item->get('ID')] ? : [])],
                    ['article_list' => (array_column($skuList[$item->get('ID')], 'article') ? : [])],
                    ['sku_id_list' => ($skuIdList ? : [])],
                    $this->getElementProperties($item),
                    $this->getAvailability($skuIdList),
                    ($this->seoData[$item->get('ID')] ? ['seo_data' => $this->seoData[$item->get('ID')]] : ['seo_data' => []])
                );
                $result[$key]['series'] = ($seriesList[$result[$key]['series']['id']] ? : $result[$key]['series']);
            } else {
                $result[$key]           = $this->getElementFields($item);
                $result[$key]['active'] = false;
            }
        }
        return array_values($result);
    }

    /**
     * Добаляет список получаемых полей элемента
     *
     * @param array $params Параметры свойств, которые необходимо получить
     *
     * @return $this
     */
    public function setSelectParams(array $params): ProductsRepository
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
    public function setFilterParams(array $params): ProductsRepository
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
    public function setPropertyElement(array $filter = [], array $select = []): ProductsRepository
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
            return $this->getElementCollection($this->iblockId, $this->getSelectList(), [])->count();
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

        $result['completion'] = [
            'input' => $this->getCompletionList($item['NAME'])
        ];

        return $result;
    }

    protected function getCompletionList(string $str): array
    {
        $result = [];
        $parts = preg_split('/\s+/', $str);
        foreach ($parts as $part) {
            $result[] = implode(' ', $parts);
            array_shift($parts);
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
            if($property['CODE'] === 'NEW') {
                if($item->get($property['CODE'])->getItem() && $item->get($property['CODE'])->getItem()->get('VALUE')) {
                    $result['is_new'] = 1;
                } else {
                    $result['is_new'] = 0;
                }
            }
            if($property['CODE'] === 'BESTSELLER') {
                if($item->get($property['CODE'])->getItem() && $item->get($property['CODE'])->getItem()->get('VALUE')) {
                    $result['is_bestseller'] = 1;
                } else {
                    $result['is_bestseller'] = 0;
                }
            }
            if($property['CODE'] === 'ONEONETHREE') {
                if($item->get($property['CODE'])->getItem() && $item->get($property['CODE'])->getItem()->get('VALUE')) {
                    $result['is_oneonethree'] = 1;
                } else {
                    $result['is_oneonethree'] = 0;
                }
            }
            $method = 'getListValueProperty' . $property['PROPERTY_TYPE'] . ucfirst(strtolower($property['USER_TYPE']));
            if (method_exists($this->property, $method) && $item->get($property['CODE']) !== null) {
                if ($property['MULTIPLE'] === 'Y') {
                    foreach ($item->get($property['CODE'])->getAll() as $value) {
                        $result[strtolower($property['CODE'])][] = $this->property->$method(
                            $value,
                            $this->referenceList[$property['CODE']]
                        );
                    }
                } else {
                    $result[strtolower($property['CODE'])] = $this->property->$method(
                        $item->get($property['CODE']),
                        $this->referenceList[$property['CODE']]
                    );
                }
            }
        }
        return $result;
    }

    protected function getElementIds($sort, $limit, $offset)
    {
        $entity = Iblock\Iblock::wakeUp($this->iblockId)->getEntityDataClass();
        unset($this->filter['IBLOCK_ID']);
        try {
            if (class_exists($entity)) {
                $query = new Iblock\ORM\Query($entity);
                $query->setSelect(['ID'])->setFilter($this->filter);
                if ($sort) {
                    $query->setOrder($sort);
                }
                if ($limit) {
                    $query->setLimit($limit);
                }
                if ($offset) {
                    $query->setOffset($offset);
                }
                if ($data = $query->exec()) {
                    $collection = $data->fetchCollection();
                    return $collection->fill('ID');
                }
            }
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Получает список торговых предложений товаров
     *
     * @param array $ids
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getSkuList(array $ids): array
    {
        $result = [];
        if ($this->skuIblockId) {
            $entity = Iblock\Iblock::wakeUp($this->skuIblockId)->getEntityDataClass();
            $data   = $entity::getList([
                'filter' => ['ACTIVE' => 'Y', 'CML2_LINK.VALUE' => $ids],
                'select' => ['ID', 'CML2_LINK', 'ARTICLE'],
            ])->fetchCollection();

            foreach ($data as $item) {
                $result[$item->get('CML2_LINK')->getValue()][] = [
                    'id' => $item->get('ID'),
                    'article' => $item->get('ARTICLE')->getValue()
                ];
            }
        }
        return $result;
    }

    /**
     *  Получает минимальную цену товара
     *
     * @param array $productIds
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getPrice(array $productIds): int
    {
        $data     = $this->prices->getPriceList($productIds);
        $minPrice = current($data);
        foreach ($data as $item) {
            $price = $item;
            if ($minPrice > $price) {
                $minPrice = $price;
            }
        }
        return $minPrice;
    }

    /**
     * Получает доступность товара по городам
     *
     * @param array $skuListIds
     *
     * @return array
     */
    public function getAvailability(array $skuListIds): array
    {
        $result['city_id_list'] = [];
        $availability = 0;
        $storeList              = $this->stores->getStroesList($skuListIds);
        foreach ($storeList as $skuId => $item) {
            $result['city_id_list'] = $item['city_id_list'];
            if($item['warehouses']) {
                foreach ($item['warehouses'] as $warehouse) {
                    $availability += $warehouse['quantity'];
                }
            }
        }
        $result['availability'] = ($availability > 0 ? 1 : 0);
        return $result;
    }

    protected function getSaleActionList(int $id, array $ids): array
    {
        global $DB;

        $result   = [];
        $iblockId = $this->getIblockId(ConstantEntityInterface::IBLOCK_ACTION);
        if ($iblockId && $ids) {
            $entity = Iblock\Iblock::wakeUp($iblockId)->getEntityDataClass();
            $data   = $entity::getList([
                'filter' => [
                    '=ACTIVE' => 'Y',
                    'PRODUCT_LIST.VALUE' => array_merge([$id], $ids),
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
                    ]
                ],
                'select' => ['ID', 'NAME', 'PRODUCT_LIST.ELEMENT'],
            ])->fetchCollection();
            if ($data->count() > 0) {
                foreach ($data as $item) {
                    $result[] = $item->get('ID');
                }
            }
        }
        return $result;
    }

    protected function getCollectionList(array $ids): array
    {
        $result   = [];
        $iblockId = $this->getIblockId(ConstantEntityInterface::IBLOCK_COLLECTION);
        if ($iblockId && $ids) {
            $entity = Iblock\Iblock::wakeUp($iblockId)->getEntityDataClass();
            $data   = $entity::getList([
                'filter' => ['ACTIVE' => 'Y', 'PRODUCT_LIST.VALUE' => $ids],
                'select' => ['ID', 'NAME', 'PRODUCT_LIST.ELEMENT'],
            ])->fetchCollection();
            if ($data->count() > 0) {
                foreach ($data as $item) {
                    $collectionList = $item->get('PRODUCT_LIST')->getAll();
                    foreach ($collectionList as $collectionItem) {
                        $result[$collectionItem->get('ELEMENT')->get('ID')][] = $item->get('ID');
                    }
                }
            }
        }
        return $result;
    }

    protected function getSeriesList(array $ids): array
    {
        $result = [];
        if ($ids) {
            $seriesList = (new SeriesRepository(ConstantEntityInterface::IBLOCK_BRAND))->all([
                'ID' => $ids,
            ], 0);
            foreach ($seriesList as $seriesItem) {
                $result[$seriesItem['id']] = [
                    'id'             => $seriesItem['id'],
                    'code'           => $seriesItem['code'],
                    'name'           => $seriesItem['name'],
                    'image'          => $seriesItem['image'],
                    'description'    => $seriesItem['excerpt'],
                    'video'          => $seriesItem['video'],
                    'commercialName' => $seriesItem['commercialName']
                ];
            }
        }
        return $result;
    }
}
