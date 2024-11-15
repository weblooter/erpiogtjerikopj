<?php

namespace NaturaSiberica\Api\Repositories\Marketing;

use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\ORM\Query;
use Bitrix\Main\FileTable;
use Bitrix\Main\ORM\Objectify\Collection;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class PromoBannerRepository
{
    use InfoBlockTrait, FileTrait;

    protected int $iblockId;
    protected int $count = 0;
    protected array $selectList = [
        'ID',
        'NAME',
        'CODE',
        'DETAIL_PICTURE',
        'PREVIEW_TEXT',
        'POSITION.ITEM'
    ];


    public function __construct(string $code)
    {
        $this->iblockId = $this->getIblockId($code);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    public function setSelect(array $data, $isMerge = false): PromoBannerRepository
    {
        if($isMerge) {
            $this->selectList = array_merge($this->selectList, $data);
        } else {
            $this->selectList = $data;
        }

        return $this;
    }

    /**
     * @param array $filter
     * @param int   $limit
     * @param int   $offset
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function all(array $filter, int $limit, int $offset = 0): array
    {
        $result = [];
        if($this->iblockId) {
            $collection = $this->getCollection($filter, $limit, $offset);
            if($collection) {
                $imageList = $this->getImagePath($collection->fill('DETAIL_PICTURE'));
                foreach ($collection as $item) {
                    $result[] = [
                        'id' => $item->get('ID'),
                        'name' => $item->get('NAME'),
                        'href' => $item->get('CODE'),
                        'image' => ($item->get('DETAIL_PICTURE') && $imageList[$item->get('DETAIL_PICTURE')]
                            ? $imageList[$item->get('DETAIL_PICTURE')]
                            : ''
                        ),
                        'description' => $item->get('PREVIEW_TEXT'),
                        'position' => (
                        $item->get('POSITION')->getItem() !== null
                            ? $item->get('POSITION')->getItem()->get('XML_ID')
                            : ''
                        ),
                    ];
                }
            }
        }
        return $result;
    }

    /**
     * @param array $filter
     * @param int   $limit
     * @param int   $offset
     *
     * @return Collection|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getCollection(array $filter, int $limit = 0, int $offset = 0): ?Collection
    {
        $entity = Iblock::wakeUp($this->iblockId)->getEntityDataClass();
        $query = (new Query($entity))
            ->setOrder(['SORT' => 'ASC', 'TIMESTAMP_X' => 'desc'])
            ->setSelect($this->selectList)
            ->setFilter($filter);
        if($limit) {
            $query->setLimit($limit);
        }

        $data = $query->fetchCollection();
        $this->count = ($data ? $data->count() : 0);

        return $data;
    }

}
