<?php

namespace NaturaSiberica\Api\Repositories\Marketing;

use Bitrix\Iblock\EO_Element;
use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Result;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class BannerHomepageRepository extends BannerRepository
{
    use InfoBlockTrait, FileTrait;

    protected array $selectList = [
        'ID',
        'NAME',
        'CODE',
        'PREVIEW_TEXT',
        'DETAIL_PICTURE',
        'POSITION',
    ];

    public function findByPosition(string $position): array
    {
        $runtime    = [
            new Reference(
                'ENUM', PropertyEnumerationTable::class, Join::on('this.POSITION.VALUE', 'ref.ID')
            ),
        ];
        $filter     = [
            'ENUM.XML_ID' => $position,
        ];

        $select = ['ENUM'];
        
        $collection = $this->getCollection($filter, 1, 0, $runtime, $select)->fetchCollection();
        return $this->prepareResult($collection);
    }

    protected function getCollection(array $filter = [], int $limit = 0, int $offset = 0, array $runtime = [], array $select = []): Result
    {
        $query = Iblock::wakeUp($this->iblockId)->getEntityDataClass()::query();
        $query->setFilter($filter);
        $query->setSelect(array_merge($this->selectList, $select));
        $query->setOrder(['SORT' => 'ASC', 'TIMESTAMP_X' => 'ASC']);

        if ($limit) {
            $query->setLimit($limit);
        }
        if ($offset) {
            $query->setOffset($offset);
        }
        if ($runtime) {
            foreach ($runtime as $key => $field) {
                if (is_numeric($key)) {
                    $query->registerRuntimeField($field);
                } else {
                    $query->registerRuntimeField($key, $field);
                }
            }
        }

        return $query->exec();
    }

    private function prepareResult(Collection $collection)
    {
        $result = [];

        if ($collection->count()) {
            $detailPicturesIds = $collection->fill('DETAIL_PICTURE');
            $positionValuesIds = $collection->fill('POSITION')->fill('VALUE');
            $positionValues    = $this->getPropertyEnumList($positionValuesIds, 'XML_ID');
            $imagesList        = $this->getImagePath($detailPicturesIds);

            /**
             * @var EO_Element $object
             */
            foreach ($collection as $object) {
                $imageId    = $object->getDetailPicture();
                $positionId = $object->get('POSITION')->get('VALUE');
                $position   = $positionValues[$positionId];

                $result[$position] = [
                    'id'          => $object->getId(),
                    'name'        => $object->getName(),
                    'href'        => $object->getCode(),
                    'position'    => $position,
                    'image'       => $imagesList[$imageId],
                    'previewText' => $object->getPreviewText(),
                ];
            }
        }

        return $result;
    }

    /**
     * @param string $xmlId
     *
     * @return array
     * @throws Exception
     */
    public function findByXmlId(string $xmlId): array
    {
        $filter = [
            'XML_ID' => $xmlId,
        ];

        $result = $this->findAll($filter, 1);

        if (empty($result)) {
            throw new Exception(Loc::getMessage('error_banner_not_found'), StatusCodeInterface::STATUS_NOT_FOUND);
        }

        return $result[$xmlId];
    }

    public function findAll($filter = [], $limit = 0): array
    {
        if (! $this->iblockId) {
            throw new ArgumentNullException('iblockId');
        }

        $collection = $this->getCollection($filter, $limit)->fetchCollection();

        return $this->prepareResult($collection);
    }
}
