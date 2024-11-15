<?php

namespace NaturaSiberica\Api\Repositories\Catalog;

use Bitrix\Iblock\Model\Section;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class CategoryRepository implements ConstantEntityInterface
{
    use InfoBlockTrait, FileTrait;

    protected int $iblockId;
    protected int $count = 0;
    protected array $selectList = [
        'ID',
        'CODE',
        'NAME',
        'IBLOCK_SECTION_ID',
        'DEPTH_LEVEL',
        'PICTURE',
        'DESCRIPTION',
        'IS_NEW' => 'UF_NEW'
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
    public function all(array $filter, int $limit, int $offset): array
    {
        $result = [];
        $data = $this->getData($filter, $limit, $offset);
        if($data) {
            $imageList = $this->getImagePath(array_column($data, 'PICTURE'));
            $metaDataList = $this->getMetaData($this->iblockId, 'S', array_column($data, 'ID'));
            foreach ($data as $item) {
                $result[] = [
                    'id' => intval($item['ID']),
                    'code' => $item['CODE'],
                    'name' => $item['NAME'],
                    'image' => ($imageList[$item['PICTURE']] ?: ''),
                    'description' => $item['DESCRIPTION'],
                    'parentId' => ($item['IBLOCK_SECTION_ID'] ? intval($item['IBLOCK_SECTION_ID']) : 0),
                    'depthLevel' => $item['DEPTH_LEVEL'],
                    'isNew' => ($item['IS_NEW'] > 0),
                    'seoData' => [
                        'title' => ($metaDataList[$item['ID']]['SECTION_META_TITLE'] ?: ''),
                        'description' => ($metaDataList[$item['ID']]['SECTION_META_DESCRIPTION'] ?: ''),
                        'pageName' => ($metaDataList[$item['ID']]['SECTION_PAGE_TITLE'] ?: ''),
                        'keywords' => ($metaDataList[$item['ID']]['SECTION_META_KEYWORDS'] ?: '')
                    ]
                ];
            }
        }
        return $result;
    }

    /**
     * @param array $filter
     * @param       $limit
     * @param       $offset
     *
     * @return array
     */
    protected function getData(array $filter, $limit, $offset): array
    {
        $entity = Section::compileEntityByIblock($this->iblockId);
        if($entity) {
            $query = [
                'order' => ['SORT' => 'ASC'],
                'filter' => array_merge(['IBLOCK_ID' => $this->iblockId, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'], ($filter ?: [])),
                'select' => $this->selectList,
                'count_total' => 1
            ];
            if($limit) {
                $query['limit'] = $limit;
            }
            if($offset && !$filter) {
                $query['offset'] = $offset;
            }
            $data = $entity::getList($query);
            $this->count = $data->getCount();
            return $data->fetchAll();
        }
        return [];
    }

}
