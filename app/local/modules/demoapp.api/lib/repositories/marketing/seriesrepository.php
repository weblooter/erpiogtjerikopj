<?php

namespace NaturaSiberica\Api\Repositories\Marketing;

use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\ORM\CommonElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Iblock\ORM\Query;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\ElasticSearch\QueryBuilder;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Content\VideoArchiveRepository;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;
use NaturaSiberica\Api\Logger\Logger;

class SeriesRepository
{
    use InfoBlockTrait, FileTrait;

    private Logger   $logger;
    protected int   $iblockId;
    protected int   $count      = 0;
    protected array $selectList = [
        'ID',
        'NAME',
        'CODE',
        'IS_POPULAR',
        'ACTIVE_FROM',
        'ACTIVE_TO',
        'DETAIL_PICTURE',
        'PREVIEW_TEXT',
        'DETAIL_TEXT',
        'PREVIEW_TEXT',
        'IBLOCK_SECTION',
        'VIDEOARCHIVE_LINK',
        'VIDEOARCHIVE' => 'V.LINK.VALUE',
        'IMAGE_DESC.VALUE',
        'COMMERCIAL_NAME'
    ];

    /**
     * @var CommonElementTable|string|null
     */
    protected                        $videoArchiveEntity = null;
    protected VideoArchiveRepository $videoArchiveRepository;

    public function __construct(string $code)
    {
        $this->logger = Logger::getInstance('files');
        $this->iblockId               = $this->getIblockId($code);
        $this->videoArchiveRepository = new VideoArchiveRepository();
        $this->setVideoArchiveEntity();
    }

    private function setVideoArchiveEntity()
    {
        $videoArchiveIblockId     = $this->getIblockId('videoarchive');
        $this->videoArchiveEntity = Iblock::wakeUp($videoArchiveIblockId)->getEntityDataClass();
    }

    /**
     * @param array $filter
     * @param int   $limit
     * @param int   $offset
     *
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public function all(array $filter, int $limit, int $offset = 0): array
    {
        $result = [];
        if ($this->iblockId) {
            $collection = $this->getCollection($filter, $limit, $offset);
            if ($collection) {
                $videos    = $this->videoArchiveRepository->all(true);
                $imageList = $this->getImagePath(
                    array_merge(
                        $collection->fill('PREVIEW_PICTURE'),
                        $collection->fill('DETAIL_PICTURE')
                    )
                );
                foreach ($collection as $item) {
                    $videoArchiveLink      = $item->sysGetRuntime('V');
                    $videoArchiveElementId = $videoArchiveLink !== null ? (int)$videoArchiveLink->get('ID') : $videoArchiveLink;
                    $video                 = $videoArchiveElementId > 0 ? $videos[$videoArchiveElementId] : $videoArchiveLink;

                    $imageDesc = ($item->get('IMAGE_DESC') && $item->get('IMAGE_DESC')->getValue()
                        ? $this->getImagePath([$item->get('IMAGE_DESC')->getValue()])
                        : [$item->get('IMAGE_DESC')->getValue() => '']);

                    $result[] = [
                        'id'          => $item->get('ID'),
                        'code'        => $item->get('CODE'),
                        'name'        => $item->get('NAME'),
                        'isPopular' => $item->get('IS_POPULAR') && $item->get('IS_POPULAR')->getValue(),
                        'excerpt'     => $item->get('PREVIEW_TEXT'),
                        'description' => $item->get('DETAIL_TEXT'),
                        'image'       => ($imageList[$item->get('DETAIL_PICTURE')] ? : ''),
                        'imageDesc'       => $imageDesc[$item->get('IMAGE_DESC')->getValue()],
                        'icon'        => ($imageList[$item->get('PREVIEW_PICTURE')] ? : ''),
                        'brandId'     => $item->getIblockSection()->getId(),
                        'collectionProducts' => $this->getCollectionProducts($item->get('ID')),
                        'video'       => ($video ? $video->toArray() : []),
                        'commercialName' => $item->get('COMMERCIAL_NAME')->getValue()
                    ];
                }
            }
        }
        return $result;
    }

    protected function getCollectionProducts(int $seriesId): int
    {
        try {
            $builder = new QueryBuilder();
            $count = $builder
                ->setIndex(ConstantEntityInterface::IBLOCK_CATALOG)
                ->setFields(['id'])
                ->setFilter(['term' => ['series_id' => $seriesId]])
                ->count();
        } catch (\Exception $e) {
            $this->logger->warning('Не удалось получить количестов товаров для линейки id '.$seriesId);
        }
        return $count ?? 0;
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
     * @return Collection|null
     * @throws ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws SystemException
     */
    protected function getCollection(array $filter, int $limit, int $offset = 0): ?Collection
    {
        $entity = Iblock::wakeUp($this->iblockId)->getEntityDataClass();
        $query  = (new Query($entity))->setOrder(['SORT' => 'ASC'])->setSelect($this->selectList)->setFilter(
            array_merge(['ACTIVE' => 'Y', 'SECTIONS.ACTIVE' => 'Y'], $filter)
        );

        $query->registerRuntimeField(
            new Reference('V', $this->videoArchiveEntity, Join::on('this.VIDEOARCHIVE_LINK.VALUE', 'ref.ID'))
        );

        $data = $query->fetchCollection();

        $this->count = ($data ? $data->count() : 0);

        if ($limit) {
            $query->setLimit($limit);
        }
        if ($offset) {
            $query->setOffset($offset);
        }

        return $query->fetchCollection();
    }
}
