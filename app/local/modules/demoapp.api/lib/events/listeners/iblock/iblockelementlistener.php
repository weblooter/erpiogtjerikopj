<?php

namespace NaturaSiberica\Api\Events\Listeners\Iblock;

use Bitrix\Catalog\CatalogIblockTable;
use Bitrix\Iblock\PropertyTable;
use NaturaSiberica\Api\Events\Handlers\Iblock\ElasticHandler;
use NaturaSiberica\Api\Events\Handlers\Iblock\ProductHandler;

/**
 * Слушатель событий инфоблоков
 *
 * Class IblockListener
 *
 * @package NaturaSiberica\Api\Events\Listeners\Iblock
 */
class IblockElementListener
{
    private static bool  $isHandlerDisallowed = false;
    private static array $catalogIdList       = [];
    private static array $catalogProductIdList       = [];
    private static array $propertyNewIdList       = [];

    public static function OnBeforeIBlockElementUpdate(array &$fields)
    {
        if (self::$isHandlerDisallowed) {
            return;
        }
        if (! self::$catalogIdList) {
            self::setCatalogIdList();
        }

        if (! in_array($fields['IBLOCK_ID'], self::$catalogIdList)) {
            return;
        }

        self::$isHandlerDisallowed = true;

        $handler = new ProductHandler((int)$fields['IBLOCK_ID'], (int)$fields['ID']);

        if (! $handler->isProductIblock()) {
            self::$isHandlerDisallowed = false;
            return;
        }

        if ($fields['ACTIVE'] === 'N' || ! $handler->checkProductActivityDates()) {
            $handler->handle();
        }

        self::$isHandlerDisallowed = false;
    }

    protected static function setCatalogIdList(): void
    {
        $data                = CatalogIblockTable::getList(['select' => ['IBLOCK_ID']])->fetchAll();
        self::$catalogIdList = array_map(function ($item) {
            return (int)$item['IBLOCK_ID'];
        }, $data);
    }

    public static function OnAfterIBlockElementDelete(array &$fields)
    {
        if (self::$isHandlerDisallowed) {
            return;
        }

        if (! self::$catalogIdList) {
            self::setCatalogIdList();
        }

        if (! in_array((int)$fields['IBLOCK_ID'], self::$catalogIdList)) {
            return;
        }

        self::$isHandlerDisallowed = true;

        self::getElasticHandler()->deleteProduct($fields);

        self::$isHandlerDisallowed = false;
    }

    /**
     * @return ElasticHandler|null
     */
    public static function getElasticHandler(): ?ElasticHandler
    {
        return new ElasticHandler();
    }

    public function OnBeforeIBlockElementAdd(&$arFields)
    {
        self::setCatalogProductIdList();
        if(!self::$catalogProductIdList || !in_array($arFields['IBLOCK_ID'], self::$catalogProductIdList)) {
            return;
        }

        self::setPropertyNewIdList();
        if(!self::$propertyNewIdList) {
            return;
        }

        foreach ($arFields['PROPERTY_VALUES'] as $key => $value) {
            if(self::$propertyNewIdList[$key]) {
                $arFields['PROPERTY_VALUES'][$key] = [
                    ['VALUE' => self::$propertyNewIdList[$key]]
                ];
            }
        }
    }

    protected static function setPropertyNewIdList()
    {
        $data = PropertyTable::getList([
            'filter' => ['IBLOCK_ID' => self::$catalogProductIdList, 'CODE' => 'NEW'],
            'select' => ['ID', 'ENUM_ID' => 'ENUM.ID'],
            'runtime' => [
                'ENUM' => [
                    'data_type' => '\Bitrix\Iblock\PropertyEnumerationTable',
                    'reference' => ['=this.ID' => 'ref.PROPERTY_ID']
                ]
            ]
        ])->fetchAll();
        if($data) {
            foreach ($data as $item) {
                self::$propertyNewIdList[$item['ID']] = $item['ENUM_ID'];
            }
        }

    }

    protected static function setCatalogProductIdList()
    {
        $data = CatalogIblockTable::getList([
            'filter' => ['PRODUCT_IBLOCK_ID' => false],
            'select' => ['IBLOCK_ID']
        ])->fetchAll();
        if($data) {
            foreach ($data as $item) {
                self::$catalogProductIdList[] = $item['IBLOCK_ID'];
            }
        }
    }
}
