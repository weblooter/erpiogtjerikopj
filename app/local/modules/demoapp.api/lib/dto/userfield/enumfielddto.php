<?php

namespace NaturaSiberica\Api\DTO\UserField;

use Spatie\DataTransferObject\DataTransferObject;

class EnumFieldDTO extends DataTransferObject
{
    /**
     * @var array
     */
    public array $list;
}
