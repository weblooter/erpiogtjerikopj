<?php

namespace NaturaSiberica\Api\Repositories\Content;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\Content\VideoTutorialsDTO;
use NaturaSiberica\Api\Helpers\Http\UrlHelper;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ServerRequestFactory;

Loc::loadMessages(__FILE__);

class VideotutorialsRepository
{
    use FileTrait;

    const IBLOCK_VIDEOTUTORIALS_CODE = 'videotutorials';

    private Query $query;
    private array $select = [
        'ID',
        'NAME',
        'CODE',
        'DETAIL_TEXT',
        'DETAIL_PICTURE',
        'LINK'                         => 'URL.VALUE',
        'DURATION'                     => 'VIDEO_DURATION.VALUE',
        'OTHER_VIDEO_ID'               => 'OTHER_VIDEOS.VALUE',
        'OTHER_VIDEO_NAME'             => 'OV.NAME',
        'OTHER_VIDEO_CODE'             => 'OV.CODE',
        'OTHER_VIDEO_DESCRIPTION'      => 'OV.DETAIL_TEXT',
        'OTHER_VIDEO_IMAGE'            => 'OV.DETAIL_PICTURE',
        'OTHER_VIDEO_LINK'             => 'OV.URL.VALUE',
        'OTHER_VIDEO_DURATION'         => 'OV.VIDEO_DURATION.VALUE',
        'INTERESTED_VIDEO_ID'          => 'INTERESTED_VIDEOS.VALUE',
        'INTERESTED_VIDEO_NAME'        => 'IV.NAME',
        'INTERESTED_VIDEO_CODE'        => 'IV.CODE',
        'INTERESTED_VIDEO_DESCRIPTION' => 'IV.DETAIL_TEXT',
        'INTERESTED_VIDEO_IMAGE'       => 'IV.DETAIL_PICTURE',
        'INTERESTED_VIDEO_LINK'        => 'IV.URL.VALUE',
        'INTERESTED_VIDEO_DURATION'    => 'IV.VIDEO_DURATION.VALUE',
    ];

    private array $rows = [];

    private array $otherVideos      = [];
    private array $interestedVideos = [];

    /**
     * @var VideoTutorialsDTO[]
     */
    private array $collection = [];

    public function __construct()
    {
        $this->setQuery();
    }

    private function setQuery(): VideotutorialsRepository
    {
        $entity      = IblockTable::compileEntity(self::IBLOCK_VIDEOTUTORIALS_CODE);
        $this->query = $entity->getDataClass()::query()->setSelect($this->select);

        $this->query->setCacheTtl(ModuleInterface::ONE_DAY_IN_SECONDS)->cacheJoins(true);

        $this->query->registerRuntimeField(
            new Reference(
                'OV', $entity->getDataClass(), Join::on('this.OTHER_VIDEO_ID', 'ref.ID')
            )
        );

        $this->query->registerRuntimeField(
            new Reference('IV', $entity->getDataClass(), Join::on('this.INTERESTED_VIDEO_ID', 'ref.ID'))
        );

        return $this;
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @return VideoTutorialsDTO[]
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function all(): array
    {
        if (empty($this->collection)) {
            $this->prepareCollection();
        }

        return $this->collection;
    }

    /**
     * @return VideoTutorialsDTO
     */
    public function get(): VideoTutorialsDTO
    {
        return $this->collection[0];
    }

    /**
     * @param $field
     * @param $value
     *
     * @return $this
     *
     * @throws Exception
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function findBy($field, $value): VideotutorialsRepository
    {
        $this->query->addFilter($field, $value);

        if (empty($this->query->fetchAll())) {
            throw new Exception(
                Loc::getMessage('error_videotutorial_not_found'), StatusCodeInterface::STATUS_NOT_FOUND
            );
        }

        $this->prepareCollection();

        return $this;
    }

    /**
     * @param int $id
     *
     * @return $this
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function findById(int $id): VideotutorialsRepository
    {
        return $this->findBy('ID', $id);
    }

    /**
     * @param string $code
     *
     * @return $this
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function findByCode(string $code): VideotutorialsRepository
    {
        return $this->findBy('CODE', $code);
    }

    /**
     * @return VideotutorialsRepository
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function prepareCollection(): VideotutorialsRepository
    {
        $all = $this->query->fetchAll();

        if (empty($all)) {
            return $this;
        }

        $imgIds  = array_column($all, 'DETAIL_PICTURE');
        $imgIds2 = array_column($all, 'OTHER_VIDEO_IMAGE');
        $imgIds3 = array_column($all, 'INTERESTED_VIDEO_IMAGE');

        $images = $this->getImagePath(array_merge($imgIds, $imgIds2, $imgIds3));

        foreach ($all as $item) {
            $id = (int)$item['ID'];

            $this->prepareRow($item, $images);
        }

        foreach ($this->rows as $row) {
            $this->collection[] = new VideoTutorialsDTO($row);
        }

        return $this;
    }

    private function prepareRow(array $row, array $images)
    {
        $id     = (int)$row['ID'];
        $imgId  = (int)$row['DETAIL_PICTURE'];
        $imgId2 = (int)$row['OTHER_VIDEO_IMAGE'];
        $imgId3 = (int)$row['INTERESTED_VIDEO_IMAGE'];

        $this->rows[$id] = [
            'id'          => $id,
            'name'        => $row['NAME'],
            'code'        => $row['CODE'],
            'description' => $row['DETAIL_TEXT'],
            'image'       => $images[$imgId] ? : null,
            'url'         => $row['LINK'],
            'duration'    => $row['DURATION'] ? : null,
        ];

        if (! empty($row['OTHER_VIDEO_ID'])) {
            $ovId                          = (int)$row['OTHER_VIDEO_ID'];
            $this->otherVideos[$id][$ovId] = [
                'id'          => (int)$row['OTHER_VIDEO_ID'],
                'name'        => $row['OTHER_VIDEO_NAME'],
                'code'        => $row['OTHER_VIDEO_CODE'],
                'description' => $row['OTHER_VIDEO_DESCRIPTION'] ? : null,
                'image'       => $images[$imgId2] ? : null,
                'url'         => $row['OTHER_VIDEO_LINK'],
                'duration'    => $row['OTHER_VIDEO_DURATION'] ? : null,
            ];
        }

        if (! empty($row['INTERESTED_VIDEO_ID'])) {
            $ivId                               = (int)$row['INTERESTED_VIDEO_ID'];
            $this->interestedVideos[$id][$ivId] = [
                'id'          => (int)$row['INTERESTED_VIDEO_ID'],
                'name'        => $row['INTERESTED_VIDEO_NAME'],
                'code'        => $row['INTERESTED_VIDEO_CODE'],
                'description' => $row['INTERESTED_VIDEO_DESCRIPTION'] ? : null,
                'image'       => $images[$imgId3] ? : null,
                'url'         => $row['INTERESTED_VIDEO_LINK'],
                'duration'    => $row['INTERESTED_VIDEO_DURATION'] ? : null,
            ];
        }

        if (array_key_exists($id, $this->otherVideos)) {
            $this->rows[$id]['otherVideos'] = array_values($this->otherVideos[$id]);
        }

        if (array_key_exists($id, $this->interestedVideos)) {
            $this->rows[$id]['interestedVideos'] = array_values($this->interestedVideos[$id]);
        }
    }
}
