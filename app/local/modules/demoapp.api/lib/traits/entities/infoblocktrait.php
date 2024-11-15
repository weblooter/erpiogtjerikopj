<?php

namespace NaturaSiberica\Api\Traits\Entities;

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserFieldTable;
use Bitrix\Iblock\InheritedProperty;

Loader::includeModule('iblock');
Loader::includeModule('highloadblock');

trait InfoBlockTrait
{
    /**
     * Получает числовой идентификатор иноблока
     *
     * @param string $code символьный идентификатор иноблока
     *
     * @return int
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getIblockId(string $code): int
    {
        $data = Iblock\IblockTable::getList(['filter' => ['CODE' => $code], 'select' => ['ID']])->fetch();
        return ($data ? intval($data['ID']) : 0);
    }

    /**
     * Получает числовой идентификатор иноблока торговых предложений
     *
     * @param int $catalogIblockId
     *
     * @return int
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getSkuIblockId(int $catalogIblockId): int
    {
        $data = CatalogIblockTable::getList(['filter' => ['PRODUCT_IBLOCK_ID' => $catalogIblockId], 'select' => ['IBLOCK_ID']])->fetch();
        return ($data ? intval($data['IBLOCK_ID']) : 0);
    }

    /**
     *
     * Получает свойства инфоблока
     *
     * @param int   $iblockId числовой идентификатор инфоблока
     * @param array $filter   параметры фильтрации
     * @param array $select   список получаемых полей
     * @param array $runtime  список референсов
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getIblockPropertyList(int $iblockId, array $filter = [], array $select = [], array $runtime = []): array
    {
        $result     = [];
        $properties = Iblock\PropertyTable::getList([
            'filter'  => array_merge(['=IBLOCK_ID' => $iblockId], $filter),
            'select'  => array_merge(
                ['ID', 'CODE', 'PROPERTY_TYPE', 'MULTIPLE', 'FILTRABLE', 'SEARCHABLE', 'USER_TYPE', 'USER_TYPE_SETTINGS_LIST', 'LINK_IBLOCK_ID'],
                $select
            ),
            'group'   => ['ID'],
            'runtime' => array_merge([
                'SHOW'   => [
                    'data_type' => 'Bitrix\Iblock\PropertyFeature',
                    'reference' => ['=this.ID' => 'ref.PROPERTY_ID'],
                ],
                'FILTER' => [
                    'data_type' => 'Bitrix\Iblock\SectionProperty',
                    'reference' => [
                        '=this.IBLOCK_ID' => 'ref.IBLOCK_ID',
                        '=this.ID'        => 'ref.PROPERTY_ID',
                    ],
                ],
            ], $runtime),
        ])->fetchAll();

        foreach ($properties as $property) {
            $result[$property['CODE']] = $property;
        }

        return $result;
    }

    /**
     * Список пользовательских полей раздела инфоблока
     *
     * @param int   $iblockId
     * @param array $filter
     * @param array $select
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getIblockSectionPropertyList(int $iblockId, array $filter = [], array $select = []): array
    {
        $result = [];

        $filter['ENTITY_ID'] = ! empty($filter['ENTITY_ID']) ? array_merge($filter['ENTITY_ID'], [$this->getIblockSectionUfEntityId($iblockId)]
        ) : $this->getIblockSectionUfEntityId($iblockId);

        $select     = array_merge($select, ['ID', 'ENTITY_ID', 'FIELD_NAME', 'USER_TYPE_ID', 'XML_ID', 'IS_SEARCHABLE', 'MULTIPLE', 'SETTINGS']);
        $properties = UserFieldTable::getList([
            'filter' => $filter,
            'select' => $select,
        ])->fetchAll();

        if (! empty($properties)) {
            foreach ($properties as $property) {
                $result[$property['FIELD_NAME']] = $property;
            }
        }

        return $result;
    }

    /**
     * Название объекта пользовательского поля раздела
     *
     * @param int $iblockId
     *
     * @return string
     */
    public function getIblockSectionUfEntityId(int $iblockId): string
    {
        return sprintf('IBLOCK_%d_SECTION', $iblockId);
    }

    /**
     * Получает сущность инфоблока по символьному API-коду
     *
     * @param string $iblockApiCode Символьный API-код инфоблока
     *
     * @return Iblock\ElementEntity|Iblock\ORM\ElementEntity|false
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getEntity(string $iblockApiCode)
    {
        return Iblock\IblockTable::compileEntity($iblockApiCode);
    }

    /**
     * Получает ID элемента инфоблока по символьному коду
     *
     * @param int    $iblockId Числовой идентификатор инфоблока
     * @param string $code     Символьный код элемента
     *
     * @return int
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getElementId(int $iblockId, string $code): int
    {
        $params = [
            'filter' => [
                'IBLOCK_ID' => $iblockId,
                'CODE'      => $code,
            ],
            'select' => ['ID', 'NAME', 'CODE'],
        ];

        $element = Iblock\ElementTable::getList($params)->fetch();
        return (int)$element['ID'];
    }

    /**
     * Получает значения свойства инфоблока типа "Список"
     *
     * @param int|array $valueIds   Числовые идентификаторы значений
     * @param string    $valueField Поле, значение которого нужно вернуть
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getPropertyEnumList($valueIds, string $valueField): array
    {
        return $this->getPropertyEnumListRawData(['ID' => $valueIds], $valueField);
    }

    public function getPropertyEnumListRawData(array $filter, string $valueField, bool $usePropertyCode = false): array
    {
        $result = [];
        $params = [
            'filter' => $filter,
            'select' => ['ID', $valueField],
        ];

        if ($usePropertyCode) {
            $params['select'][] = 'PROPERTY';
        }

        $collection = Iblock\PropertyEnumerationTable::getList($params)->fetchCollection();

        foreach ($collection as $object) {
            $id = $object->getId();
            $value = $object->get($valueField);

            if ($usePropertyCode) {
                $propertyCode = $object->getProperty()->get('CODE');
                $result[$propertyCode][$id] = $value;
            } else {
                $result[$id] = $value;
            }
        }

        return $result;
    }

    /**
     * Получает символьный код инфоблока по ID
     *
     * @param int $iblockId Числовой идентификатор инфоблока
     *
     * @return mixed
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getIblockCodeById(int $iblockId)
    {
        $iblock = Iblock\IblockTable::getByPrimary($iblockId, ['select' => ['CODE']])->fetch();
        return $iblock['CODE'];
    }

    /**
     * Получает значение свойства "Text/HTML"
     *
     * @param EntityObject|null $object
     *
     * @return string|void
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getPropertyHtmlValue(EntityObject $object = null)
    {
        if ($this->getPropertyObjectValue($object)) {
            $value = unserialize($this->getPropertyObjectValue($object));
            return $value['TEXT'];
        }
    }

    /**
     * Получает значение объекта свойства
     *
     * @param EntityObject|Collection|null $object
     *
     * @return mixed|void
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getPropertyObjectValue($object = null)
    {
        if ($object instanceof EntityObject) {
            return $object->get('VALUE');
        } elseif ($object instanceof Collection) {
            $values = [];

            foreach ($object as $item) {
                $values[] = $item->get('VALUE');
            }

            return $values;
        }
    }

    /**
     * Получает значения свойства типа "Привязка к элементам инфоблока"
     *
     * @param int    $linkIblockId Числовой идентификатор инфоблока, чьи элементы выводятся в свойстве
     * @param array  $elementIds   Значения свойства элемента
     * @param string $valueField   Поле, значение которого нужно возвращать
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getPropertyElementValues(int $linkIblockId, array $elementIds, string $valueField): array
    {
        $result     = [];
        $collection = $this->getElementCollection($linkIblockId, ['ID', $valueField], ['ID' => $elementIds]);

        foreach ($collection as $object) {
            $result[$object->getId()] = $object->get($valueField);
        }

        return $result;
    }

    /**
     * Получает коллекцию элементова инфоблока
     *
     * @param int   $iblockId
     * @param array $select
     * @param array $filter
     * @param array $sort
     * @param int   $limit
     * @param int   $offset
     *
     * @return Collection|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getElementCollection(
        int $iblockId,
        array $select = [],
        array $filter = [],
        array $sort = [],
        int $limit = 0,
        int $offset = 0
    ): ?Collection {
        $select = ($select ? : ['ID']);
        $entity = Iblock\Iblock::wakeUp($iblockId)->getEntityDataClass();
        if ($filter['IBLOCK_ID']) {
            unset($filter['IBLOCK_ID']);
        }
        if (class_exists($entity)) {
            $query = new Iblock\ORM\Query($entity);
            $data  = $query->setSelect($select)->setFilter($filter);
            if ($sort) {
                $query->setOrder($sort);
            }
            if ($limit) {
                $query->setLimit($limit);
            }
            if ($offset) {
                $query->setOffset($offset);
            }
            $query->exec();
            if ($data) {
                return $data->fetchCollection();
            }
        }
        return null;
    }

    /**
     * Получает значение свойства типа "Привязка к разделам"
     *
     * @param array  $sectionsIds Значения свойства элемента
     * @param string $valueField  Поле, значение которого нужно возвращать
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getPropertySectionValues(array $sectionsIds, string $valueField): array
    {
        $result     = [];
        $collection = Iblock\SectionTable::getList(['filter' => ['ID' => $sectionsIds], 'select' => ['ID', $valueField]])->fetchCollection();

        foreach ($collection as $object) {
            $result[$object->getId()] = $object->get($valueField);
        }

        return $result;
    }

    /**
     * Получает значение свойства типа "Привязка к highload-блокам"
     *
     * @param string $entityName Название сущности
     * @param array  $filter
     * @param string $valueField Поле, значение которого нужно возвращать
     * @param string $idField
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getPropertyHighloadBlockValue(string $entityName, string $valueField, array $filter = [], string $idField = 'ID'): array
    {
        $result     = [];
        $dataClass  = HighloadBlockTable::compileEntity(ucfirst($entityName))->getDataClass();
        $collection = $dataClass::getList(['filter' => $filter, 'select' => [$idField, $valueField]])->fetchCollection();

        foreach ($collection as $object) {
            $result[$object->get($idField)] = $object->get($valueField);
        }

        return $result;
    }

    /**
     *
     * Получает мета данные элемента или раздела инфоблока
     *
     * @param int    $iblockId числовой идентификатор иноблока
     * @param string $type     Тип сущности (принимает значения E - элемент, S - раздел)
     * @param array  $ids      Список числовых идентификаторов элементов или разделов
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getMetaData(int $iblockId, string $type, array $ids = []): array
    {
        $result = [];
        $method = 'getMetaTemplate' . $type;
        if (method_exists($this, $method)) {
            if ($ids) {
                foreach ($ids as $id) {
                    $result[$id] = $this->$method($iblockId, $id);
                }
            } else {
                $result = $this->$method($iblockId);
            }
        } else {
            $data = Iblock\InheritedPropertyTable::getList([
                'filter' => ['IBLOCK_ID' => $iblockId, 'ENTITY_TYPE' => $type, 'ENTITY_ID' => $ids],
                'select' => ['CODE', 'TEMPLATE', 'ENTITY_ID'],
            ])->fetchCollection();
            foreach ($data as $item) {
                $result[$item->get('ENTITY_ID')][$item->get('CODE')] = $item->get('TEMPLATE');
            }
        }

        return $result;
    }

    protected function getMetaTemplateB(int $iblockId): array
    {
        $result = new InheritedProperty\IblockValues($iblockId);
        return ($result->getValues() ?? []);
    }

    protected function getMetaTemplateS(int $iblockId, int $sectionId): array
    {
        $result = new InheritedProperty\SectionValues($iblockId, $sectionId);
        return ($result->getValues() ?? []);
    }

    protected function getMetaTemplateE(int $iblockId, int $elementId = null): array
    {
        if (! $elementId) {
            return [];
        }
        $result = new InheritedProperty\ElementValues($iblockId, $elementId);
        return ($result->getValues() ?? []);
    }
}
