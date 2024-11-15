<?php

namespace NaturaSiberica\Api\Repositories\Marketing;

use Bitrix\Iblock\EO_Element;
use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\ORM\Query;
use Bitrix\Main\ORM\Query\Result;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class BannerRepository
{
    use InfoBlockTrait, FileTrait;

    protected int   $iblockId;
    protected int   $count      = 0;
    protected array $selectList = [
        'NAME',
        'CODE',
        'PREVIEW_TEXT',
        'DETAIL_PICTURE',
        'BANNER_MP',
        'IS_PREVIEW_FIRST',
        'BUTTON_TEXT',
        'IS_BUTTON_LIGHT',
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
    public function all(array $filter, int $limit, int $offset = 0): array
    {
        $result = [];
        if ($this->iblockId) {
            $collection = $this->getCollection($filter, $limit, $offset)->fetchCollection();
            if ($collection->count()) {
                $detailPicturesIds = $collection->fill('DETAIL_PICTURE');
                $bannerMpIds       = $collection->fill('BANNER_MP')->fill('VALUE');
                $checkboxValuesIds = array_merge(
                    $collection->fill('IS_PREVIEW_FIRST')->fill('VALUE'),
                    $collection->fill('IS_BUTTON_LIGHT')->fill('VALUE')
                );
                $checkboxValues    = $this->getPropertyEnumList($checkboxValuesIds, 'XML_ID');
                $imageList         = $this->getImagePath(array_merge_recursive($detailPicturesIds, $bannerMpIds));

                /**
                 * @var EO_Element $item
                 */
                foreach ($collection as $item) {
                    $detailPictureId  = $item->get('DETAIL_PICTURE');
                    $bannerMpId       = ! empty($item->get('BANNER_MP')) ? $item->get('BANNER_MP')->get('VALUE') : null;
                    $isPreviewFirstId = $item->get('IS_PREVIEW_FIRST') ? $item->get('IS_PREVIEW_FIRST')->get('VALUE') : null;
                    $isButtonLightId  = $item->get('IS_BUTTON_LIGHT') ? $item->get('IS_BUTTON_LIGHT')->get('VALUE') : null;

                    $result[] = [
                        'name'           => $item->get('NAME'),
                        'previewText'    => $item->getPreviewText() ? : null,
                        'isPreviewFirst' => $checkboxValues[$isPreviewFirstId] === 'Y',
                        'buttonText'     => $item->get('BUTTON_TEXT') ? $item->get('BUTTON_TEXT')->get('VALUE') : null,
                        'isButtonLight'  => $checkboxValues[$isButtonLightId] === 'Y',
                        'image'          => $imageList[$detailPictureId],
                        'mobileImage'    => $imageList[$bannerMpId],
                        'href'           => $item->get('CODE') ? : null,
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
     * @return Result
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getCollection(array $filter, int $limit, int $offset = 0): Result
    {
        $entity = Iblock::wakeUp($this->iblockId)->getEntityDataClass();
        $query  = (new Query($entity))->setOrder(['SORT' => 'ASC', 'TIMESTAMP_X' => 'ASC'])->setSelect($this->selectList)->setFilter($filter);

        $data        = $query->fetchCollection();
        $this->count = ($data ? $data->count() : 0);

        if ($limit) {
            $query->setLimit($limit);
        }
        if ($offset) {
            $query->setOffset($offset);
        }

        return $query->exec();
    }

}
