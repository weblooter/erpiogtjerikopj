<?php

namespace NaturaSiberica\Api\Repositories\Catalog;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\ORM\Entity;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;

class ElementReferenceRepository
{
    use HighloadBlockTrait, FileTrait;

    /**
     * @param array $property
     *
     * @return array
     */
    public function getReference(array $property): array
    {
        $result    = [];
        $reference = $this->getReferenceList($property);
        foreach ($reference as $referenceCode => $tableName) {
            $result[$referenceCode] = $this->getReferencePost($tableName);
        }
        return $result;
    }

    /**
     * Получает список названий таблиц спровочников
     *
     * @param array $propertyList
     *
     * @return array
     */
    public function getReferenceList(array $propertyList): array
    {
        $result = [];
        foreach ($propertyList as $property) {
            if ($property['USER_TYPE'] === 'directory') {
                $result[$property['CODE']] = $property['USER_TYPE_SETTINGS_LIST']['TABLE_NAME'];
            }
        }
        return $result;
    }

    /**
     * Получает записи спровочника
     *
     * @param string $tableName
     *
     * @return array
     */
    public function getReferencePost(string $tableName): array
    {
        $result = [];
        try {
            $entity = $this->getHlEntityByTableName($tableName);
            $fields = $this->getFieldsList($entity);
            $data   = (new Query($entity))->setSelect(array_keys($fields))->exec()->fetchAll();
            if ($data) {
                foreach ($data as $item) {
                    $result[$item['UF_XML_ID']] = $this->getReferenceValue($item, $fields);
                }
            }
            return $result;
        } catch (\Exception $e) {
        }
        return $result;
    }

    /**
     * Получает символьные коды полей справочника
     *
     * @param Entity $entity
     *
     * @return \string[][]
     */
    public function getFieldsList(Entity $entity): array
    {
        $result    = [
            'ID'        => ['type' => 'integer', 'code' => 'id'],
            'UF_XML_ID' => ['type' => 'string', 'code' => 'xml_id'],
        ];
        $fieldList = $this->getHLListFieldByFilter($entity, ['=IS_SEARCHABLE' => 'Y'], ['USER_TYPE_ID', 'XML_ID']);
        foreach ($fieldList as $fieldItem) {
            $result[$fieldItem['FIELD_NAME']] = [
                'type' => $fieldItem['USER_TYPE_ID'],
                'code' => strtolower($fieldItem['XML_ID']),
            ];
        }
        return $result;
    }

    /**
     * Получает значения полей записей
     *
     * @param array $data
     * @param array $fields
     *
     * @return array
     */
    public function getReferenceValue(array $data, array $fields): array
    {
        $result = [];
        foreach ($data as $code => $value) {
            $item = $fields[$code];
            if ($item['type'] === 'file') {
                $result['image'] = ($value ? $this->getImagePath([$value])[$value] : '');
            } else {
                $result[$item['code']] = $value;
            }
        }
        return $result;
    }

}
