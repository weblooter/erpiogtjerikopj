<?php

namespace Userstory\ItsIntegrator;

use Userstory\ItsIntegrator\Settings\Options;

class Helper
{
    public static function prepareImageGroupCode($iblockCode, $fieldName, $remove = null): string
    {
        $search = Options::getFieldSeparatorSearch();
        $separator = Options::getFieldSeparatorReplace();

        $groupCode = sprintf('%s-%s', $iblockCode, $fieldName);
        $groupCode = strtolower($groupCode);
        $groupCode = str_ireplace($search, $separator, $groupCode);

        if ($remove) {
            $groupCode = str_ireplace($remove, '', $groupCode);
        }

        return rtrim($groupCode, $separator);
    }
}
