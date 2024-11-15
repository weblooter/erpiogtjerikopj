<?php

namespace NaturaSiberica\Api\Repositories\Content;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Traits\Entities\FileTrait;

Loc::loadMessages(__FILE__);

class NewsRepository
{
    use FileTrait;

    const IBLOCK_NEWS_CODE = 'news';

    private Query $query;
    private array $select = [
        'ID',
        'NAME',
        'CODE',
        'DATE_CREATE',
        'PREVIEW_TEXT',
        'PREVIEW_PICTURE',
        'TOP_TEXT_DETAIL'        => 'TOP_TEXT.VALUE',
        'MIDDLE_TEXT_DETAIL'     => 'MIDDLE_TEXT.VALUE',
        'BOTTOM_TEXT_DETAIL'     => 'BOTTOM_TEXT.VALUE',
        'TOP_PRODUCTS_DETAIL'    => 'TOP_PRODUCTS.VALUE',
        'MIDDLE_PRODUCTS_DETAIL' => 'MIDDLE_PRODUCTS.VALUE',
        'BOTTOM_PRODUCTS_DETAIL' => 'BOTTOM_PRODUCTS.VALUE',
    ];

    private array $rows = [];

    /**
     * @var array
     */
    private array $collection = [];

    public function __construct()
    {
        $this->setQuery();
    }

    private function setQuery(): NewsRepository
    {
        $entity      = IblockTable::compileEntity(self::IBLOCK_NEWS_CODE);
        $this->query = $entity->getDataClass()::query()->setSelect($this->select)->setFilter(['ACTIVE' => 'Y'])->setOrder(['DATE_CREATE' => 'DESC']);

        $this->query->setCacheTtl(ModuleInterface::ONE_DAY_IN_SECONDS)->cacheJoins(true);

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
     * @param int $limit
     * @param int $offset
     *
     * @return array
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function all(int $limit = 0, int $offset = 0): array
    {
        $this->setQuery();
        $this->query->setSelect(['ID']);
        if ($limit) {
            $this->query->setLimit($limit);
        }
        if ($offset) {
            $this->query->setOffset($offset);
        }
        $newsIds = $this->query->fetchAll();
        $this->setQuery();
        $this->query->setFilter(['ID' => array_map(fn($newsId) => $newsId['ID'], $newsIds)]);
        $all = $this->query->fetchAll();
        if (empty($this->collection)) {
            $this->prepareCollection($all);
        }

        return $this->collection;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->collection[0];
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function count()
    {
        $this->query->registerRuntimeField("CNT", array(
                "data_type" => "integer",
                'expression' => array('COUNT(%s)', 'ID')
            )
        )->setSelect(['CNT'])->setOrder([]);
        $count = $this->query->fetch();
        return $count['CNT'];
    }

    /**
     * @param string $code
     *
     * @return $this
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function findByCode(string $code): NewsRepository
    {
        return $this->findBy('CODE', $code);
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
    public function findBy($field, $value): NewsRepository
    {
        $this->setQuery();
        $this->query->addFilter($field, $value);

        if (empty($this->query->fetchAll())) {
            throw new Exception(
                Loc::getMessage('error_videotutorial_not_found'), StatusCodeInterface::STATUS_NOT_FOUND
            );
        }
        $all = $this->query->fetchAll();
        $this->prepareCollection($all,'show_detail');

        return $this;
    }

    /**
     * @param array $all
     *
     * @return NewsRepository
     * @throws SystemException
     */
    private function prepareCollection(array $all, string $display = 'show_list'): NewsRepository
    {
        if (empty($all)) {
            return $this;
        }

        $imgIds  = array_column($all, 'PREVIEW_PICTURE');
        $images = $this->getImagePath($imgIds);

        foreach ($all as $item) {
            $this->prepareRow($item, $images, $display);
        }

        foreach ($this->rows as $row) {
            if (!empty($row['products'])) {
                $row['products'] = array_map(fn($productIds) => array_values(array_unique($productIds)), $row['products']);
            }
            $this->collection[] = $row;
        }
        return $this;
    }

    private function prepareRow(array $row, array $images, string $display)
    {
        $id = (int)$row['ID'];

        if ($display == 'show_detail') {
            $detailData = [
                'detailText' => [
                    'top'    => unserialize($row['TOP_TEXT_DETAIL'])['TEXT'],
                    'middle' => unserialize($row['MIDDLE_TEXT_DETAIL'])['TEXT'],
                    'bottom' => unserialize($row['BOTTOM_TEXT_DETAIL'])['TEXT'],
                ],
                'products'   => [
                    'top'    => array_merge($this->rows[$id]['products']['top'] ? : [], [(int)$row['TOP_PRODUCTS_DETAIL']]),
                    'middle' => array_merge($this->rows[$id]['products']['middle'] ? : [], [(int)$row['MIDDLE_PRODUCTS_DETAIL']]),
                    'bottom' => array_merge($this->rows[$id]['products']['bottom'] ? : [], [(int)$row['BOTTOM_PRODUCTS_DETAIL']]),
                ],
                'seo'        => $this->prepareSeo($id),
            ];
        }

        $this->rows[$id] = array_merge(
            [
                'name'         => $row['NAME'],
                'code'         => $row['CODE'],
                'dateCreate'   => $row['DATE_CREATE']->toString(),
                'previewImage' => $images[$row['PREVIEW_PICTURE']],
                'previewText'  => $row['PREVIEW_TEXT'],
            ],
            $detailData ? : []
        );
    }

    private function prepareSeo(int $id): array
    {
        $iblockId = IblockTable::compileEntity(self::IBLOCK_NEWS_CODE)->getIblock()->getId();
        $valuesObject = new \Bitrix\Iblock\InheritedProperty\ElementValues($iblockId, $id);
        $seo = $valuesObject->getValues();

        return [
            'metaTitle' => $seo['ELEMENT_META_TITLE'],
            'metaKeywords' => $seo['ELEMENT_META_KEYWORDS'],
            'metaDescription' => $seo['ELEMENT_META_DESCRIPTION'],
            'pageTitle' => $seo['ELEMENT_PAGE_TITLE']
        ];
    }
}
