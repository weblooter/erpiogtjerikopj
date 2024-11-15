<?php

namespace NaturaSiberica\Api\Repositories\Marketing;

use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\ORM\Query;
use Bitrix\Main\ORM\Objectify\Collection;
use Exception;
use NaturaSiberica\Api\DTO\Marketing\SaleActionDTO;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class SaleActionRepository
{
    use InfoBlockTrait, FileTrait;

    protected int   $iblockId;
    protected int   $count      = 0;
    protected array $selectList = [
        'ID',
        'NAME',
        'CODE',
        'IS_WHITE',
        'ACTIVE_FROM',
        'ACTIVE_TO',
        'DETAIL_PICTURE',
        'PREVIEW_PICTURE',
        'PREVIEW_TEXT',
        'DETAIL_TEXT',
        'PREVIEW_TEXT',
        'PRODUCT_LIST.ELEMENT',
    ];

    public function __construct(string $code)
    {
        $this->iblockId = $this->getIblockId($code);
    }

    public function count(): int
    {
        return $this->count;
    }

    public function all(array $filter, int $limit, int $offset = 0): array
    {
        $result = [];
        if ($this->iblockId) {
            $collection = $this->getCollection($filter, $limit, $offset);
            if ($collection) {
                $metaDataList = $this->getMetaData($this->iblockId, 'E', $collection->fill('ID'));
                $imageList    = $this->getImagePath(
                    array_merge(
                        $collection->fill('DETAIL_PICTURE'),
                        $collection->fill('PREVIEW_PICTURE')
                    )
                );
                foreach ($collection as $item) {
                    $productList = $this->getPropertyElementValue($item, 'PRODUCT_LIST', 'ID');
                    $result[]    = [
                        'id'          => $item->get('ID'),
                        'name'        => $item->get('NAME'),
                        'code'        => $item->get('CODE'),
                        'timeFrom'    => ($item->get('ACTIVE_FROM') ? $item->get('ACTIVE_FROM')->format("Y m d") : ''),
                        'timeTo'      => ($item->get('ACTIVE_TO') ? $item->get('ACTIVE_TO')->format("Y m d") : ''),
                        'image'       => ($imageList[$item->get('DETAIL_PICTURE')] ? $imageList[$item->get('DETAIL_PICTURE')] : ''),
                        'bannerMP'    => ($imageList[$item->get('PREVIEW_PICTURE')] ? $imageList[$item->get('PREVIEW_PICTURE')] : ''),
                        'isWhite'     => (bool)$item->get('IS_WHITE')->getValue(),
                        'description' => $item->get('DETAIL_TEXT'),
                        'productList' => ($productList ? $productList['list'] : []),
                        'seoData'     => [
                            'title'       => ($metaDataList[$item->get('ID')]['ELEMENT_META_TITLE'] ? : ''),
                            'description' => ($metaDataList[$item->get('ID')]['ELEMENT_META_DESCRIPTION'] ? : ''),
                            'pageName'    => ($metaDataList[$item->get('ID')]['ELEMENT_PAGE_TITLE'] ? : ''),
                        ],
                    ];
                }
            }
        }
        return $result;
    }

    // TODO: переделать
    protected function getCollection(array $filter, int $limit, int $offset = 0): ?Collection
    {
        $entity = Iblock::wakeUp($this->iblockId)->getEntityDataClass();
        if ($limit) {
            $query = (new Query($entity))->setOrder(['SORT' => 'ASC', 'TIMESTAMP_X' => 'ASC'])->setSelect(['ID'])->setFilter(
                array_merge(['IBLOCK_ID' => $this->iblockId, 'ACTIVE' => 'Y'], $filter)
            )->setLimit($limit);
            if ($offset && ! $filter) {
                $query->setOffset($offset);
            }
            $data   = $query->fetchCollection();
            $filter = ['ID' => $data->fill('ID')];
        }

        $query       = (new Query($entity))->setOrder(['SORT' => 'ASC', 'TIMESTAMP_X' => 'ASC'])->setSelect($this->selectList)->setFilter(
            array_merge(['IBLOCK_ID' => $this->iblockId, 'ACTIVE' => 'Y'], $filter)
        );
        $data        = $query->fetchCollection();
        $this->count = ($data ? $data->count() : 0);

        return $data;
    }

    /**
     * @param $item
     * @param $propertyCode
     * @param $code
     *
     * @return array
     */
    protected function getPropertyElementValue($item, $propertyCode, $code): array
    {
        $result = [];
        $data   = $item->get($propertyCode);
        if ($data instanceof Collection) {
            foreach ($data->getAll() as $item) {
                $result['list'][] = $item->getElement()->get($code);
            }
        } else {
            $result['value'] = $item->getElement()->get($code);
        }
        return $result;
    }
}
