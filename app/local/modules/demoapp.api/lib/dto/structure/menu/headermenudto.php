<?php

namespace NaturaSiberica\Api\DTO\Structure\Menu;

use Spatie\DataTransferObject\DataTransferObject;

class HeaderMenuDTO extends DataTransferObject
{
    public int $id;
    public ?int $parent = null;
    public string $name;
    public ?string $url = null;
    public bool $isNew = false;
    public int $sort;
    public bool $bold;
    public bool $showArrow;

    public static function convertFromBitrixFormat(array $fields)
    {
        return new static([
            'id' => (int) $fields['ID'],
            'parent' => (int) $fields['IBLOCK_SECTION_ID'],
            'name' => $fields['NAME'],
            'url' => $fields['UF_URL'],
            'isNew' => (bool) $fields['UF_IS_NEW'],
            'sort' => (int) $fields['SORT'],
            'bold' => (bool) $fields['UF_BOLD'],
            'showArrow' => (bool) $fields['UF_SHOW_ARROW']
        ]);
    }
}
