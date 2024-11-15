<?php

namespace NaturaSiberica\Api\Repositories\Marketing;

use Bitrix\Iblock\Model\Section;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\Helpers\ContentHelper;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class BrandRepository
{
    use InfoBlockTrait, FileTrait;

    protected array $selectList = [
        'ID',
        'CODE',
        'NAME',
        'PICTURE',
        'ICON'                => 'DETAIL_PICTURE',
        'SHORT_DESC'          => 'UF_SHORT_DESCRIPTION',
        'MOBILE_APP_IMAGE'    => 'UF_MOBILE_APP_IMAGE',
        'DESCRIPTION',
        'DESCRIPTION2'        => 'UF_DESCRIPTION2',
        'IMAGE_DESC'          => 'UF_IMAGE_DESC',
        'ENABLE_RICH_CONTENT' => 'UF_ENABLE_RICH_CONTENT',
    ];
    protected int   $iblockId;
    protected int   $count      = 0;

    public function __construct(string $code)
    {
        $this->iblockId = $this->getIblockId($code);
    }

    /**
     * @param array $filter
     * @param int   $limit
     * @param int   $offset
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function all(array $filter, int $limit, int $offset): array
    {
        $result = [];
        $data   = $this->getData($filter, $limit, $offset);
        if ($data) {
            $imageList = $this->getImagePath(
                array_merge(
                    array_column($data, 'PICTURE'),
                    array_column($data, 'ICON'),
                    array_column($data, 'MOBILE_APP_IMAGE'),
                    array_column($data, 'IMAGE_DESC'),
                )
            );
            $metaData  = $this->getMetaData($this->iblockId, 'S', array_column($data, 'ID'));
            foreach ($data as $item) {
                $result[] = [
                    'id'                => intval($item['ID']),
                    'code'              => $item['CODE'],
                    'name'              => $item['NAME'],
                    'enableRichContent' => (bool)$item['ENABLE_RICH_CONTENT'],
                    'image'             => ($imageList[$item['PICTURE']] ? : null),
                    'icon'              => ($imageList[$item['ICON']] ? : null),
                    'mobileAppImage'    => ($imageList[$item['MOBILE_APP_IMAGE']] ? : null),
                    'imageDesc'         => ($imageList[$item['IMAGE_DESC']] ? : null),
                    'description'       => ($item['DESCRIPTION'] ? ContentHelper::replaceImageUrl($item['DESCRIPTION']) : ''),
                    'description2'      => ($item['DESCRIPTION2'] ? ContentHelper::replaceImageUrl($item['DESCRIPTION2']) : ''),
                    'shotDesc'          => ($item['SHORT_DESC'] ? : ''),
                    'seoData'           => [
                        'title'       => ($metaData[$item['ID']]['SECTION_META_TITLE'] ? : null),
                        'description' => ($metaData[$item['ID']]['SECTION_META_DESCRIPTION'] ? : null),
                        'pageName'    => ($metaData[$item['ID']]['SECTION_PAGE_TITLE'] ? : null),
                    ],
                ];
            }
        }
        return $result;
    }

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
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function getData(array $filter, int $limit, int $offset): array
    {
        $entity = Section::compileEntityByIblock($this->iblockId);
        if ($entity) {
            $this->count = $entity::getList(['select' => ['ID'], 'count_total' => 1])->getCount();
            $query       = [
                'order'  => ['SORT' => 'ASC'],
                'limit'  => $limit,
                'filter' => array_merge(['IBLOCK_ID' => $this->iblockId, 'ACTIVE' => 'Y'], $filter),
                'select' => $this->selectList,
            ];
            if (! $filter) {
                $query['offset'] = $offset;
            }

            $data = $entity::getList($query);
            return $data->fetchAll();
        }
        return [];
    }

}
