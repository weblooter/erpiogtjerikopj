<?php

namespace NaturaSiberica\Api\DTO\Content\Pages;

use Spatie\DataTransferObject\DataTransferObject;

class PageDTO extends DataTransferObject
{
    public string  $name;
    public string  $code;
    public string  $description = '';
    public ?string $image             = null;
    public bool    $isWhiteHeaderInMP = false;

    /**
     * @var PageItemDTO[]|array|null
     */
    public ?array $items = null;
}
