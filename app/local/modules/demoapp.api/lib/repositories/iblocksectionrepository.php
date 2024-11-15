<?php

namespace NaturaSiberica\Api\Repositories;

use Bitrix\Iblock\Model\Section;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class IblockSectionRepository
{
    use InfoBlockTrait;

    protected int $iblockId;
    protected Query $query;
    protected array $select = [
        'ID',
    ];

    /**
     * @param int $iblockId
     *
     * @throws ArgumentException
     * @throws SystemException
     */
    public function __construct(int $iblockId)
    {
        $this->iblockId = $iblockId;
        $this->prepareQuery();
    }

    public function getSectionIblockId(): int
    {
        return $this->iblockId;
    }

    /**
     * @throws SystemException
     * @throws ArgumentException
     */
    protected function prepareQuery()
    {
        $section = Section::compileEntityByIblock($this->iblockId);
        $this->query = $section::query();
        $this->query->setSelect($this->select);
    }

    public function getSectionUserFieldsSelect(bool $useOnlySearchableFields = false): array
    {
        $filter = $useOnlySearchableFields ? ['IS_SEARCHABLE' => 'Y'] : [];
        $fields = $this->getIblockSectionPropertyList($this->iblockId, $filter);
        return array_keys($fields);
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function find(
        array $filter = [],
        array $select = [],
        array $group = [],
        int $limit = 100,
        int $offset = 0
    ): array {
        $this->query->setFilter($filter);
        $this->query->setSelect(array_merge($this->select, $select));
        $this->query->setGroup($group);
        $this->query->setLimit($limit);
        $this->query->setOffset($offset);
        return $this->query->fetchAll();
    }

    public function findById(int $id, array $select = []): array
    {
        $data = $this->find(['ID' => $id], $select);
        return $data[0];
    }

    public function first(): array
    {
        return $this->find([], [], [], 1);
    }
}
