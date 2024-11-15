<?php

namespace NaturaSiberica\Api\DTO;

use Spatie\DataTransferObject\DataTransferObject;

class CityDTO extends DataTransferObject
{
    public int     $id;
    public string  $name;
    public ?int    $deliveryPrice = null;
    public ?string $deliveryTime  = null;
    public int     $sort;
    public ?string $latitude      = null;
    public ?string $longitude     = null;
    public array   $cases         = [];
}
