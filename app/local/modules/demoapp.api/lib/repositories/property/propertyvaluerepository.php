<?php

namespace NaturaSiberica\Api\Repositories\Property;

use Bitrix\Iblock;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;

class PropertyValueRepository
{
    use HighloadBlockTrait;

    /**
     * Получает список значений свойства
     *
     * @param $data
     *
     * @return array|null
     */
    public function getPropertyValue($data): ?array
    {
        $method = 'getValueProperty' . $data['type'] . ucfirst(strtolower($data['user_type']));
        if (method_exists($this, $method)) {
            return $this->$method($data);
        }
        return null;
    }

    /**
     * Получает значения для свойств тип Список
     *
     * @param array $params
     *
     * @return array
     */
    protected function getValuePropertyL(array $params): array
    {
        $result = [];
        try {
            $select = ['ID', 'VALUE', 'XML_ID'];
            $data   = (new Iblock\ORM\Query((new Iblock\PropertyEnumerationTable())::getEntity()))->setOrder(['SORT' => 'asc'])->setFilter(
                    ['=PROPERTY_ID' => $params['id']]
                )->setSelect($select)->exec()->fetchCollection();
            if ($data->count() > 0) {
                foreach ($data as $key => $item) {
                    foreach ($select as $value) {
                        if ($value == 'XML_ID') {
                            $code = 'code';
                        } else {
                            $code = strtolower($value);
                        }
                        $result[$key][$code] = $item->get($value);
                    }
                }
            }
            return array_values($result);
        } catch (\Exception $e) {
            return $result;
        }
    }

    /**
     * Получает значения для свойств тип Привязка к элементам
     *
     * @param array $params
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getValuePropertyE(array $params): array
    {
        $result = [];
        try {
            $select = ['ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID', 'IBLOCK_SECTION'];
            $data   = (new Iblock\ORM\Query((new Iblock\ElementTable())::getEntity()))->setOrder(['SORT' => 'asc'])->setFilter(
                    ['=IBLOCK_ID' => $params['link_iblock_id']]
                )->setSelect($select)->exec()->fetchCollection();
            if ($data->count() > 0) {
                foreach ($data as $key => $item) {
                    foreach ($select as $value) {
                        $referenceField = '';
                        if ($value == 'NAME') {
                            $code = 'value';
                        } elseif ($value == 'IBLOCK_SECTION_ID') {
                            $code = 'parentId';
                        } elseif ($value == 'IBLOCK_SECTION') {
                            $code = 'parentValue';
                            $referenceField = 'NAME';
                        } else {
                            $code = strtolower($value);
                        }
                        $resultValue = $item->get($value);
                        if (!empty($referenceField)) {
                            $resultValue = $resultValue->get($referenceField);
                        }
                        $result[$key][$code] = $resultValue;
                    }
                }
            }
            return array_values($result);
        } catch (\Exception $e) {
            return $result;
        }
    }

    /**
     * Получает значения для свойств тип Привязка к разделам
     *
     * @param array $params
     *
     * @return array
     */
    protected function getValuePropertyG(array $params): array
    {
        $result = [];
        try {
            $select = ['ID', 'NAME', 'CODE'];
            $data   = (new Iblock\ORM\Query((new Iblock\SectionTable())::getEntity()))->setOrder(['SORT' => 'asc'])->setFilter(
                    ['=IBLOCK_ID' => $params['link_iblock_id']]
                )->setSelect($select)->exec()->fetchCollection();
            if ($data->count()) {
                foreach ($data as $key => $item) {
                    foreach ($select as $value) {
                        if ($value == 'NAME') {
                            $code = 'value';
                        } else {
                            $code = strtolower($value);
                        }
                        $result[$key][$code] = $item->get($value);
                    }
                }
            }
            return array_values($result);
        } catch (\Exception $e) {
            return $result;
        }
    }

    /**
     * Получает значения для свойств тип Справочник
     *
     * @param array $params
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getValuePropertySDirectory(array $params): array
    {
        $result = [];
        $entity = $this->getHlEntityByTableName($params['settings']['TABLE_NAME']);
        $select = array_merge(['ID', 'UF_XML_ID'], $this->getHLListFieldByFilter($entity, ['=IS_SEARCHABLE' => 'Y']));
        $data   = (new Iblock\ORM\Query($entity))->setFilter(['=UF_ACTIVE' => true])->setSelect($select)->exec()->fetchCollection();
        if ($data->count() > 0) {
            foreach ($data as $key => $item) {
                foreach ($select as $value) {
                    if ($value === 'UF_XML_ID') {
                        $code = 'code';
                    } else {
                        $code = strtolower(str_replace('UF_', '', $value));
                    }
                    $result[$key][$code] = $item->get($value);
                }
            }
        }
        return array_values($result);
    }
}
