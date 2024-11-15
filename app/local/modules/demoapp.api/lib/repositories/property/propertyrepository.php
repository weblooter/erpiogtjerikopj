<?php

namespace NaturaSiberica\Api\Repositories\Property;

use Bitrix\Iblock;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Objectify\Collection;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

Loader::includeModule('iblock');

class PropertyRepository
{
    use InfoBlockTrait;

    protected int $iblockId;
    protected array $filter = [];
    protected array $select = [
        'ID',
        'SORT',
        'ACTIVE',
        'NAME',
        'CODE',
        'PROPERTY_TYPE',
        'MULTIPLE',
        'FILTRABLE',
        'SEARCHABLE',
        'USER_TYPE',
        'USER_TYPE_SETTINGS',
        'LINK_IBLOCK_ID'
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
        $this->iblockId = $this->getIblockId($code);
    }

    /**
     * Получает список свойств
     *
     * @param int    $limit Количесвто запрашиваемых элементов
     * @param int    $offset Количество элементов, которые необходимо пропустить
     *
     * @return array
     */
    public function get(int $limit = 0, int $offset = 0): array
    {
        $result = [];
        try {
            $data = $this->getIblockProperty($limit, $offset);
            if($data) {
                foreach ($data as $item) {
                    $result[] = array_merge(
                        $item,
                        $this->getPropertyFilterable($item['id']),
                        $this->getPropertyShow($item['id'])
                    );
                }
            }
        } catch (\Exception $e){}
        return $result;
    }

    /**
     * Добаляет список получаемых полей свойств
     *
     * @param array $params Параметры свойств, которые необходимо получить
     *
     * @return $this
     */
    public function setSelectParamsProperty(array $params): PropertyRepository
    {
        $this->select = array_merge($this->select, $params);
        return $this;
    }

    /**
     * Добавляет параметры фильтрации
     *
     * @param array $params Параметры фильтрмции свойств инфоблока
     *
     * @return $this
     */
    public function setFilterParamsProperty(array $params): PropertyRepository
    {
        $this->filter = array_merge($this->select, $params);
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
            return $this->getCollection('Property', array_merge(['=IBLOCK_ID' => $this->iblockId], $this->filter), ['ID'])->count();
        } catch (\Exception $e) {
            dump($e);
            return 0;
        }
    }

    /**
     * Получает список свойств инфоблока
     *
     * @param int $limit
     * @param int $offset
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getIblockProperty(int $limit = 0, int $offset = 0): array
    {
        $data = $this->getCollection(
            'Property',
            array_merge(['=IBLOCK_ID' => $this->iblockId], $this->filter),
            $this->select,
            $limit,
            $offset
        );
        $result = [];
        if($data->count()) {
            foreach ($data as $item) {
                $result[$item->get('ID')] = [
                    'id' => $item->get('ID'),
                    'sort' => $item->get('SORT'),
                    'active' => $item->get('ACTIVE'),
                    'name' => $item->get('NAME'),
                    'code' => $item->get('CODE'),
                    'type' => $item->get('PROPERTY_TYPE'),
                    'user_type' => $item->get('USER_TYPE'),
                    'settings' => ($item->get('USER_TYPE_SETTINGS') ? unserialize($item->get('USER_TYPE_SETTINGS')) : []),
                    'link_iblock_id' => $item->get('LINK_IBLOCK_ID'),
                    'multiple' => $item->get('MULTIPLE'),
                    'searchable' => $item->get('SEARCHABLE')
                ];
            }
        }
        return $result;
    }

    /**
     * Добавляет поля принадлежности к фильтру и разделам каталога
     *
     * @param int $id
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getPropertyFilterable(int $id): array
    {
        $data = $this->getCollection(
            'SectionProperty',
            ['=PROPERTY_ID' => $id],
            ['SECTION_ID', 'SECTION.IBLOCK_SECTION_ID', 'SMART_FILTER', 'DISPLAY_TYPE']
        );
        $result = ['section_id' => [],'display_type' => '', 'filterable' => false];
        if($data->count()) {
            foreach ($data as $item) {
                if($item->get('SMART_FILTER')) {
                    $result['section_id'][] = $item->get('SECTION_ID');

                    if ($item->get('SECTION') && empty($item->get('SECTION')->get('IBLOCK_SECTION_ID'))) {
                        $childSections = Iblock\SectionTable::getList([
                            'filter' => ['IBLOCK_SECTION_ID' => $item->get('SECTION_ID')],
                            'select' => ['ID']
                        ])->fetchAll();

                        if (!empty($childSections)) {
                            $ids = array_map(function ($childSection) {
                                return (int) $childSection['ID'];
                            }, $childSections);
                            $result['section_id'] = array_merge($result['section_id'], $ids);
                        }
                    }

                    $result['display_type'] = $item->get('DISPLAY_TYPE');
                    $result['filterable'] = $item->get('SMART_FILTER');
                }
            }
        }
        return $result;
    }

    /**
     * Добавляет поля показа свойств на листинге и деатальной карточке товара
     *
     * @param int $id
     *
     * @return false[]
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getPropertyShow(int $id): array
    {
        $data = $this->getCollection(
            'PropertyFeature',
            ['=PROPERTY_ID' => $id],
            ['FEATURE_ID', 'IS_ENABLED']
        );
        $result = ['show_list' => false,'show_detail' => false];
        if($data->count()) {
            foreach ($data as $item) {
                if($item->get('FEATURE_ID') === 'LIST_PAGE_SHOW') {
                    $result['show_list'] = $item->get('IS_ENABLED');
                }
                if($item->get('FEATURE_ID') === 'DETAIL_PAGE_SHOW') {
                    $result['show_detail'] = $item->get('IS_ENABLED');
                }
            }
        }
        return $result;
    }

    /**
     * @param string $name
     * @param array  $filter
     * @param array  $select
     * @param int    $limit
     * @param int    $offset
     *
     * @return Collection|null
     */
    protected function getCollection(string $name, array $filter, array $select, int $limit = 0, int $offset = 0): ?Collection
    {
        try {
            $class = '\Bitrix\Iblock\\'.$name.'Table';
            $entity = (new $class)::getEntity();
            $query = new Iblock\ORM\Query($entity);
            $query->setFilter($filter)->setSelect($select);
            if($limit) $query->setLimit($limit);
            if($offset) $query->setOffset($offset);
            return $query->exec()->fetchCollection();
        } catch (\Exception $e) {
            return null;
        }
    }
}
