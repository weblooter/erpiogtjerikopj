<?php

namespace NaturaSiberica\Api\DTO\Content\Pages;

use Spatie\DataTransferObject\DataTransferObject;

class PageSeoDTO extends DataTransferObject
{
    public ?string $metaTitle = null;
    public ?string $metaDescription = null;
    public ?string $metaKeywords = null;
    public ?string $pageTitle = null;

    public static function convertFromBitrixFormat(array $fields)
    {
        return new static([
            'metaTitle' => $fields['ELEMENT_META_TITLE'],
            'metaKeywords' => $fields['ELEMENT_META_KEYWORDS'],
            'metaDescription' => $fields['ELEMENT_META_DESCRIPTION'],
            'pageTitle' => $fields['ELEMENT_PAGE_TITLE']
        ]);
    }
}
