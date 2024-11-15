<?php

namespace NaturaSiberica\Api\DTO\Sale;

use Spatie\DataTransferObject\DataTransferObject;

class PaySystemDTO extends DataTransferObject
{
    public int $id;
    public string $name;
    public ?string $code = null;
    public ?string $logo = null;
}
