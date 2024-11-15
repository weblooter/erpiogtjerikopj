<?php

namespace NaturaSiberica\Api\DTO\Structure\Menu;

use Spatie\DataTransferObject\DataTransferObject;

class FooterMenuDTO extends DataTransferObject
{
    public string $url;
    public string $text;
    /**
     * @var string|int|null
     */
    public $sort = null;

    public ?string $icon = null;
}
