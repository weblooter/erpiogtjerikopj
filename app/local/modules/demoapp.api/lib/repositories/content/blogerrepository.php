<?php

namespace NaturaSiberica\Api\Repositories\Content;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Query;
use NaturaSiberica\Api\DTO\Content\BlogerDTO;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;

class BlogerRepository
{
    use HighloadBlockTrait, FileTrait;

    const HL_ENTITY_NAME = 'Blogers';

    private Query $query;

    private array $select = [
        'name'     => 'UF_NAME',
        'position' => 'UF_POSITION',
        'photo'    => 'UF_PHOTO',
    ];

    /**
     * @var BlogerDTO[]
     */
    private array $collection = [];

    public function __construct()
    {
        $this->setQuery();
    }

    private function setQuery()
    {
        $this->query = $this->getEntity()::query()->setSelect($this->select)->setOrder(['UF_SORT' => 'asc']);
    }

    public function getEntity()
    {
        return $this->getHlEntityByEntityName(self::HL_ENTITY_NAME)->getDataClass();
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    public function all(): array
    {
        $this->prepareCollection();
        return $this->collection;
    }

    /**
     * @return $this
     *
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function prepareCollection()
    {
        $collection = $this->query->fetchCollection();

        $imagePaths = $this->getImagePath($collection->fill('UF_PHOTO'));

        /**
         * @var EntityObject $object
         */
        foreach ($collection as $object) {
            $this->collection[] = new BlogerDTO([
                'name'     => $object->get('UF_NAME'),
                'position' => $object->get('UF_POSITION'),
                'photo'    => $imagePaths[$object->get('UF_PHOTO')],
            ]);
        }

        return $this;
    }
}
