<?php

namespace Userstory\ItsIntegrator\Event;

use Userstory\ItsIntegrator\ItsProducer;

class IblockListener
{
    public static function OnAfterIBlockSectionAdd(array &$fields)
    {
        if (! in_array($fields['IBLOCK_ID'], self::getHandler()->getAllowedIblocksIds()) || ! $fields['RESULT']) {
            return;
        }

        $options = [
            'result'     => (bool)$fields['RESULT'],
            'event'      => str_ireplace([__CLASS__, '\\', '::'], '', __METHOD__),
            'iblock_id'  => $fields['IBLOCK_ID'],
            'section_id' => $fields['ID'] ?? false,
            'group_code' => ItsProducer::getImageGroupCodeByIblockId($fields['IBLOCK_ID']),
            'fields'     => $fields,
        ];

        self::getHandler()->OnAfterIBlockSectionAddHandler($options);
    }

    protected static function getHandler(): IblockHandler
    {
        return new IblockHandler();
    }

    public static function OnBeforeIBlockSectionUpdate(array &$fields)
    {
        $options = [
            'event'      => str_ireplace([__CLASS__, '\\', '::'], '', __METHOD__),
            'iblock_id'  => $fields['IBLOCK_ID'],
            'section_id' => $fields['ID'],
            'group_code' => ItsProducer::getImageGroupCodeByIblockId($fields['IBLOCK_ID']),
            'fields'     => &$fields,
        ];

        self::getHandler()->OnBeforeIBlockSectionUpdateHandler($options);
    }

    public static function OnAfterIBlockSectionUpdate(array &$fields)
    {
        $options = [
            'result'     => (bool)$fields['RESULT'],
            'event'      => str_ireplace([__CLASS__, '\\', '::'], '', __METHOD__),
            'iblock_id'  => $fields['IBLOCK_ID'],
            'section_id' => $fields['ID'] ?? false,
            'group_code' => ItsProducer::getImageGroupCodeByIblockId($fields['IBLOCK_ID']),
            'fields'     => $fields,
        ];

        self::getHandler()->OnAfterIBlockSectionUpdateHandler($options);
    }

    public static function OnBeforeIBlockSectionDelete(int $sectionId)
    {
        $iblockId = self::getHandler()->getIblockIdBySectionId($sectionId);
        $options  = [
            'event'      => str_ireplace([__CLASS__, '\\', '::'], '', __METHOD__),
            'iblock_id'  => $iblockId,
            'section_id' => $sectionId,
            'group_code' => ItsProducer::getImageGroupCodeByIblockId($iblockId),
        ];

        self::getHandler()->OnBeforeIBlockSectionDeleteHandler($options);
    }

    public static function OnAfterIBlockElementAdd(array &$fields)
    {
        if (! in_array($fields['IBLOCK_ID'], self::getHandler()->getAllowedIblocksIds()) || ! $fields['RESULT']) {
            return;
        }

        $options = [
            'result'     => (bool)$fields['RESULT'],
            'event'      => str_ireplace([__CLASS__, '\\', '::'], '', __METHOD__),
            'iblock_id'  => $fields['IBLOCK_ID'],
            'element_id' => $fields['ID'] ?? false,
            'group_code' => ItsProducer::getImageGroupCodeByIblockId($fields['IBLOCK_ID']),
            'fields'     => &$fields,

        ];

        self::getHandler()->OnAfterIBlockElementAddHandler($options);
    }

    public static function OnBeforeIBlockElementUpdate(array &$fields)
    {
        if (! in_array($fields['IBLOCK_ID'], self::getHandler()->getAllowedIblocksIds())) {
            return;
        }

        $options = [
            'event'      => str_ireplace([__CLASS__, '\\', '::'], '', __METHOD__),
            'iblock_id'  => $fields['IBLOCK_ID'],
            'element_id' => $fields['ID'],
            'group_code' => ItsProducer::getImageGroupCodeByIblockId($fields['IBLOCK_ID']),
            'fields'     => &$fields,
        ];

        self::getHandler()->OnBeforeIBlockElementUpdateHandler($options);
    }

    public static function OnAfterIBlockElementUpdate(array &$fields)
    {
        if (! in_array($fields['IBLOCK_ID'], self::getHandler()->getAllowedIblocksIds()) || ! $fields['RESULT']) {
            return;
        }

        $options = [
            'result'     => (bool)$fields['RESULT'],
            'event'      => str_ireplace([__CLASS__, '\\', '::'], '', __METHOD__),
            'iblock_id'  => $fields['IBLOCK_ID'],
            'element_id' => $fields['ID'],
            'group_code' => ItsProducer::getImageGroupCodeByIblockId($fields['IBLOCK_ID']),
            'fields'     => &$fields,
        ];

        self::getHandler()->OnAfterIBlockElementUpdateHandler($options);
    }

    public static function OnIBlockElementDelete(int $elementId)
    {
        $iblockId = self::getHandler()->getIblockIdByElementId($elementId);

        if (! in_array($iblockId, self::getHandler()->getAllowedIblocksIds())) {
            return;
        }

        $options = [
            'event'      => str_ireplace([__CLASS__, '\\', '::'], '', __METHOD__),
            'iblock_id'  => $iblockId,
            'element_id' => $elementId,
            'group_code' => ItsProducer::getImageGroupCodeByIblockId($iblockId),
        ];

        self::getHandler()->OnIBlockElementDeleteHandler($options);
    }

    public static function OnIBlockElementSetPropertyValuesEx(
        int $elementId,
        int $iblockId,
        array $propertyValues,
        array $propertyList,
        array $dbProps
    ) {
        if (! in_array($iblockId, self::getHandler()->getAllowedIblocksIds())) {
            return;
        }

        $options = [
            'event'           => str_ireplace([__CLASS__, '\\', '::'], '', __METHOD__),
            'iblock_id'       => $iblockId,
            'element_id'      => $elementId,
            'group_code'      => ItsProducer::getImageGroupCodeByIblockId($iblockId),
            'property_values' => $propertyValues,
            'property_list'   => $propertyList,
        ];

        self::getHandler()->OnIBlockElementSetPropertyValuesExHandler($options);
    }
}
