<?php

namespace NaturaSiberica\Api\DTO\Content\Pages;

use NaturaSiberica\Api\Helpers\ContentHelper;
use Spatie\DataTransferObject\DataTransferObject;

class PageItemDTO extends DataTransferObject
{
    public string      $name;
    public string      $code;
    public string      $content;
    public ?string     $image = null;
    public ?PageSeoDTO $seo     = null;

    public static function convertFromBitrixFormat(array $fields): PageItemDTO
    {
        return new static([
            'name'    => $fields['NAME'],
            'code'    => $fields['CODE'],
            'content' => ContentHelper::replaceImageUrl($fields['CONTENT']),
            'seo'     => $fields['SEO'],
            'image' => $fields['PICTURE'],
        ]);
    }
}
