<?php

namespace NaturaSiberica\Api\DTO\Sale;

use NaturaSiberica\Api\DTO\AbstractDTO;
use NaturaSiberica\Api\DTO\CityDTO;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use Spatie\DataTransferObject\DataTransferObject;

class StoreDTO extends DataTransferObject implements DTOInterface
{
    public int     $id;
    public string  $name;
    public CityDTO $city;
    public string  $address;
    public string  $latitude;
    public string  $longitude;
    public ?string $phone        = null;
    public ?string $schedule     = null;
    public ?string $metroStation = null;

    public function modify(array $fields): DTOInterface
    {
        return $this;
    }
}
