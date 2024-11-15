<?php

namespace NaturaSiberica\Api\DTO\UserField;

use Spatie\DataTransferObject\DataTransferObject;

class EnumFieldItemDTO extends DataTransferObject
{
    public int $id;
    public string $value;
}
