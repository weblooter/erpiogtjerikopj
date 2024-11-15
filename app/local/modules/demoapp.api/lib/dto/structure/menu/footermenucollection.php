<?php

namespace NaturaSiberica\Api\DTO\Structure\Menu;

use NaturaSiberica\Api\DTO\Structure\Menu\FooterMenuDTO;
use Spatie\DataTransferObject\DataTransferObject;

class FooterMenuCollection extends DataTransferObject
{
    /**
     * @var FooterMenuDTO[]|array|null
     */
    public ?array $pages = null;

    /**
     * @var FooterMenuDTO[]|array|null
     */
    public ?array $mobile = null;

    /**
     * @var FooterMenuDTO[]|array|null
     */
    public ?array $social = null;
}
