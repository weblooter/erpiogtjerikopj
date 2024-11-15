<?php

namespace NaturaSiberica\Api\Repositories\Marketing;

use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\ORM\Query;
use Exception;
use NaturaSiberica\Api\DTO\Marketing\CollectionDTO;
use NaturaSiberica\Api\Helpers\Http\UrlHelper;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;
use Bitrix\Main\ORM\Objectify\Collection;

class CollectionRepository
{
    use InfoBlockTrait, FileTrait;

    protected int   $iblockId;
    protected int   $count      = 0;
    protected array $selectList = [
        'ID',
        'NAME',
        'CODE',
        'PREVIEW_PICTURE',
        'DETAIL_PICTURE',
        'DETAIL_TEXT',
        'PRODUCT_LIST.ELEMENT',
        'STORIES_LIST.FILE',
        'BANNER_MP',
        'IS_WHITE',
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
            if ($collection->count()) {
                $imageIds        = $collection->fill('DETAIL_PICTURE');
                $previewImageIds = $collection->fill('PREVIEW_PICTURE');
                $bannerImageIds  = $collection->fill('BANNER_MP')->fill('VALUE');
                $imageList       = $this->getImagePath(array_merge_recursive($previewImageIds, $imageIds, $bannerImageIds));
                $metaDataList    = $this->getMetaData($this->iblockId, 'E', $collection->fill('ID'));

                foreach ($collection as $item) {
                    $previewImageId = $item->get('PREVIEW_PICTURE');
                    $imageId        = $item->get('DETAIL_PICTURE');
                    $bannerId       = !empty($item->get('BANNER_MP')) ? $item->get('BANNER_MP')->getValue() : null;

                    $productList = $this->getPropertyElementValue($item, 'PRODUCT_LIST', 'ID');
                    $result[]    = [
                        'id'           => $item->get('ID'),
                        'name'         => $item->get('NAME'),
                        'code'         => $item->get('CODE'),
                        'isWhite'      => $item->get('IS_WHITE') && $item->get('IS_WHITE')->getValue(),
                        'previewImage' => $imageList[$previewImageId],
                        'bannerMP'     => $imageList[$bannerId],
                        'image'        => $imageList[$imageId],
                        'description'  => $item->get('DETAIL_TEXT'),
                        'productList'  => ($productList ? $productList['list'] : []),
                        'storiesList'  => $this->getStoriesList($item),
                        'seoData'      => [
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

    protected function getStoriesList(object $item): array
    {
        $result = [];
        if ($item->get('STORIES_LIST') && ($fileList = $item->get('STORIES_LIST')->getAll())) {
            foreach ($fileList as $fileItem) {
                if ($file = $fileItem->getFile()) {
                    $typeList = explode('/', $file->get('CONTENT_TYPE'));
                    $result[] = [
                        'type' => $typeList[0],
                        'link' => UrlHelper::getFileUri($file),
                    ];
                }
            }
        }
        return $result;
    }

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
}
