<?php

namespace NaturaSiberica\Api\Repositories\Sale;

use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Internals\StatusTable;
use NaturaSiberica\Api\DTO\Sale\StatusDTO;

Loader::includeModule('sale');

class StatusRepository
{
    const STATUS_TYPE_ORDER = 'O';
    const STATUS_TYPE_DELIVERY = 'D';

    private Query $query;

    private array $select = [
        'id',
        'type',
        'sort',
        'xmlId' => 'XML_ID',
        'lang' => 'STATUS_LANG.LID',
        'name' => 'STATUS_LANG.NAME',
        'description' => 'STATUS_LANG.DESCRIPTION'
    ];

    /**
     * @var StatusDTO[]
     */
    private array $collection = [];

    public function __construct()
    {
        $this->setQuery();
    }

    private function setQuery()
    {
        $this->query = StatusTable::query();
        $this->query->setSelect($this->select)->setOrder(['sort' => 'asc']);
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    public function setStatusType(string $statusType = self::STATUS_TYPE_ORDER): StatusRepository
    {
        $this->query->addFilter('type', $statusType);
        return $this;
    }

    public function setLang(string $lang = LANGUAGE_ID): StatusRepository
    {
        $this->query->addFilter('lang', $lang);
        return $this;
    }

    /**
     * @param array $filter
     *
     * @return $this
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function findBy(array $filter): StatusRepository
    {
        $this->query->setFilter($filter);
        $this->prepareCollection();
        return $this;
    }

    public function getSelectorList(array $filter): array
    {
        $result = [];

        foreach ($this->findBy($filter)->all() as $item) {
            $result[$item->id] = sprintf('[%s] %s', $item->id, $item->name);
        }

        return $result;
    }

    /**
     * @return void
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function prepareCollection(): void
    {
        foreach ($this->query->fetchAll() as $item) {
            $this->collection[] = new StatusDTO($item);
        }
    }

    /**
     * @return StatusDTO[]
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function all(): array
    {
        if (empty($this->collection)) {
            $this->prepareCollection();
        }
        return $this->collection;
    }
}
