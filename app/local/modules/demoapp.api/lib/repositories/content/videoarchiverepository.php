<?php

namespace NaturaSiberica\Api\Repositories\Content;

use Bitrix\Iblock\EO_Element;
use Bitrix\Iblock\Iblock;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\DTO\Content\VideoDTO;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class VideoArchiveRepository
{
    use InfoBlockTrait, FileTrait;

    const IBLOCK_VIDEOARCHIVE_CODE = 'videoarchive';

    private Query $query;
    private int   $iblockId;

    private array $select = [
        'ID',
        'NAME',
        'CODE',
        'DETAIL_TEXT',
        'DETAIL_PICTURE',
        'URL' => 'LINK.VALUE',
        'DUR' => 'DURATION.VALUE',
    ];

    /**
     * @var array|VideoDTO[]
     */
    private array $collection = [];

    public function __construct()
    {
        $this->setIblockId()->setQuery();
    }

    private function setIblockId(): VideoArchiveRepository
    {
        $this->iblockId = $this->getIblockId(self::IBLOCK_VIDEOARCHIVE_CODE);
        return $this;
    }

    private function setQuery(): VideoArchiveRepository
    {
        $this->query = Iblock::wakeUp($this->iblockId)->getEntityDataClass()::query();
        $this->query->setSelect($this->select);
        return $this;
    }

    /**
     * @param string $field
     * @param        $value
     *
     * @return $this
     */
    public function findBy(string $field, $value): VideoArchiveRepository
    {
        $this->query->addFilter($field, $value);
        return $this;
    }

    /**
     * @param bool $idAsKey
     *
     * @return array
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function all(bool $idAsKey = false): array
    {
        return $this->prepareCollection($idAsKey)->collection;
    }

    /**
     * @param bool $idAsKey
     *
     * @return VideoArchiveRepository
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function prepareCollection(bool $idAsKey = false): VideoArchiveRepository
    {
        if (! empty($this->collection)) {
            return $this;
        }
        $data   = $this->query->fetchCollection();
        $images = $this->getVideosImages($data);
        /**
         * @var EO_Element $item
         */
        foreach ($data as $item) {
            if ($item === null) {
                continue;
            }

            $attrs = [
                'id'          => $item->getId(),
                'name'        => $item->getName(),
                'code'        => $item->getCode(),
                'description' => $item->getDetailText(),
                'image'       => $images[$item->getDetailPicture()],
                'url'         => $item->get('LINK')->get('VALUE'),
                'duration'    => $item->get('DURATION')->get('VALUE'),
            ];

            $dto = new VideoDTO($attrs);

            if ($idAsKey) {
                $this->collection[$item->getId()] = $dto;
            } else {
                $this->collection[] = $dto;
            }
        }

        return $this;
    }

    /**
     * @param bool $idAsKey
     *
     * @return VideoDTO
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function one(bool $idAsKey = false): VideoDTO
    {
        return $this->prepareCollection($idAsKey)->collection[0];
    }

    private function getVideosImages(Collection $collection): array
    {
        $imgIds = [];

        foreach ($collection as $item) {
            $imgIds[] = $item->getDetailPicture();
        }

        return $this->getImagePath($imgIds);
    }
}
