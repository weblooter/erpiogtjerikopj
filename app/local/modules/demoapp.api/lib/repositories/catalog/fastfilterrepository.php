<?php

namespace NaturaSiberica\Api\Repositories\Catalog;

use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Query;
use NaturaSiberica\Api\DTO\Catalog\FastFilterDTO;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;

Loader::includeModule('highloadblock');

class FastFilterRepository implements ConstantEntityInterface
{
    use HighloadBlockTrait;

    protected array $select = [
        'ID',
        'categoryUrl' => 'UF_CATEGORY_URL',
        'fullUrl'     => 'UF_FULL_URL',
        'fastUrl'     => 'UF_FAST_URL',
        'title'       => 'UF_TITLE',
        'description' => 'UF_DESCRIPTION',
        'keywords'    => 'UF_KEYWORDS',
        'h1'          => 'UF_H1',
        'name'        => 'UF_NAME',
        'text'        => 'UF_TEXT',
        'sort'        => 'UF_SORT',
        'active'      => 'UF_ACTIVE',
    ];

    protected Query $query;

    /**
     * @var FastFilterDTO[]
     */
    protected array $collection = [];

    public function __construct()
    {
        $this->setQuery();
    }

    /**
     * @return $this
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public function setQuery(): FastFilterRepository
    {
        $this->query = $this->getHlEntityByEntityName(self::HLBLOCK_FAST_FILTER)->getDataClass()::query();
        $this->query->setSelect($this->select)->addOrder('sort')->addOrder('name');

        return $this;
    }

    /**
     * @return $this
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function prepareCollection(): FastFilterRepository
    {
        $fastFilters = $this->query->fetchAll();

        if (empty($fastFilters)) {
            return $this;
        }

        foreach ($fastFilters as $fastFilter) {
            $this->collection[] = new FastFilterDTO([
                'id'          => (int)$fastFilter['ID'],
                'categoryUrl' => $fastFilter['categoryUrl'],
                'fullUrl'     => $fastFilter['fullUrl'],
                'fastUrl'     => $fastFilter['fastUrl'],
                'title'       => $fastFilter['title'],
                'description' => $fastFilter['description'],
                'keywords'    => $fastFilter['keywords'],
                'h1'          => $fastFilter['h1'],
                'name'        => $fastFilter['name'],
                'text'        => $fastFilter['text'],
                'sort'        => (int)$fastFilter['sort'],
                'active'      => ($fastFilter['active'] > 0)
            ]);
        }

        return $this;
    }

    /**
     * @return FastFilterDTO[]
     */
    public function all(): array
    {
        if (empty($this->collection)) {
            $this->prepareCollection();
        }

        return $this->collection;
    }

    /**
     * @return \Bitrix\Main\ORM\Data\DataManager|string
     */
    public static function getEntity()
    {
        return (new static())->getHlEntityByEntityName(self::HLBLOCK_FAST_FILTER)->getDataClass();
    }

    /**
     * @param array $filter
     *
     * @return $this
     */
    public function setFilter(array $filter): FastFilterRepository
    {
        foreach ($filter as $code => $value) {
            $this->query->addFilter($code, $value);
        }

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return $this
     */
    public function setLimit(int $limit): FastFilterRepository
    {
            $this->query->setLimit($limit);

        return $this;
    }
}
