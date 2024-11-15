<?php

namespace NaturaSiberica\Api\Traits\Entities;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Entity;
use NaturaSiberica\Api\Entities\FieldTable;

Loader::includeModule('highloadblock');

trait HighloadBlockTrait
{
    /**
     * Возвращает сущьность хайлоад блока
     *
     * @param string $name Наименование сущности
     *
     * @return Entity
     */
    protected function getHlEntityByEntityName(string $name): Entity
    {
        try {
            return HighloadBlockTable::compileEntity($name);
        } catch (SystemException $e) {
            return new Entity();
        }
    }

    /**
     * Получает сущность хайлоада по названию таблицы
     *
     * @param string $tableName Название таблицы
     *
     * @return Entity
     */
    protected function getHlEntityByTableName(string $tableName): Entity
    {
        try {
            $hlData = $this->getHlDataByTableName($tableName);
            return HighloadBlockTable::compileEntity($hlData['ID']);
        } catch (SystemException $e) {
            return new Entity();
        }
    }

    /**
     * Получает данные хайлоад блока по названию таблицы
     *
     * @param string $tableName название таблицы
     *
     * @return array
     * @throws SystemException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     */
    protected function getHlDataByTableName(string $tableName): array
    {
        return (HighloadBlockTable::getList(['filter' => ['=TABLE_NAME' => $tableName]])->fetch() ?: []);
    }

    /**
     * Получает все поля хайлоад блока
     * @param Entity $entity Сущность хайлоад блока
     *
     * @return array
     */
    public function getHLListFieldAll(Entity $entity): array
    {
        $fields = $entity->getFields();
        return ($fields ? array_keys($fields) : []);
    }

    /**
     * Получает поля хайлоад блока по фильтру
     *
     * @param Entity $entity сущность хайлоад блока
     * @param array  $filter параметры для отбора полей
     * @param array  $select получаемые поля
     *
     * @return array
     */
    public function getHLListFieldByFilter(Entity $entity, array $filter = [], array $select = []): array
    {
        $result = [];
        try {
            $hlData = $this->getHlDataByTableName($entity->getDBTableName());
            $query = new Query(FieldTable::getEntity());
            $data = $query
                ->setSelect(array_merge(['FIELD_NAME'], $select))
                ->setFilter(array_merge(['ENTITY_ID' => 'HLBLOCK_'.$hlData['ID']], $filter))
                ->exec()
                ->fetchAll();
            if($select) {
                return $data;
            }
            foreach ($data as $item) {
                $result[] = $item['FIELD_NAME'];
            }
        } catch (\Exception $e) {}
        return $result;
    }
}
