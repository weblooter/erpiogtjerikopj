<?php

namespace NaturaSiberica\Api\DTO\Sale;

use Spatie\DataTransferObject\DataTransferObject;

class DeliverySystemDTO extends DataTransferObject
{
    public int     $id;
    public string  $name;
    public ?string $typeCode = null;
    public ?string $logo     = null;
    public array   $variants = [];
}
