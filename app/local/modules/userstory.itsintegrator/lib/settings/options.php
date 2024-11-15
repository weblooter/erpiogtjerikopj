<?php

namespace Userstory\ItsIntegrator\Settings;

use Bitrix\Main\Config\Option;
use Userstory\ItsIntegrator\ItsConnector;

class Options
{
    public static function getImageGroups(): array
    {
        $value = Option::get(ItsConnector::MODULE_ID, "groupCodes");

        if (empty($value)) {
            return [];
        }

        $rawGroups = array_values(unserialize($value));
        $groups = [];

        foreach ($rawGroups as $rawGroup) {
            $iblockId = (int) $rawGroup['IBLOCK_ID'];

            if (!array_key_exists($iblockId, $groups)) {
                $groups[$iblockId] = [
                    $rawGroup['GROUP_NAME']
                ];
            } else {
                $groups[$iblockId][] = $rawGroup['GROUP_NAME'];
            }
        }

        return $groups;
    }

    public static function checkImageGroup(int $iblockId, string $imageGroup): bool
    {
        $groups = self::getImageGroups();
        return in_array($imageGroup, $groups[$iblockId]);
    }

    public static function getFieldSeparatorSearch(): string
    {
        return Option::get(ItsConnector::MODULE_ID, 'field_separator_search');
    }

    public static function getFieldSeparatorReplace(): string
    {
        return Option::get(ItsConnector::MODULE_ID, 'field_separator_replace');
    }
}
