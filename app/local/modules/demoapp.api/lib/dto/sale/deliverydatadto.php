<?php

namespace NaturaSiberica\Api\DTO\Sale;

use Spatie\DataTransferObject\DataTransferObject;

class DeliveryDataDTO extends DataTransferObject
{
    public int     $id;
    public string  $name;
    public string  $code;
    public int     $price;
    public int     $cityId;
    public string  $city;
    public ?string $deliveryTime = null;
    public string  $type;
    public string  $typeCode;
    public ?string $description  = null;
    public ?string $phone        = null;
    public ?string $workhours    = null;
    public string  $latitude;
    public string  $longitude;
    public bool    $closed;

    public function __construct(array $parameters = [])
    {
        $this->prepareParameters($parameters);
        parent::__construct($parameters);
    }

    private function prepareParameters(array &$parameters)
    {
        $id = (int)$parameters['ID'];
        unset($parameters['ID']);
        $parameters['id'] = $id;

        foreach ($parameters as $key => &$value) {
            if (! property_exists($this, $key)) {
                continue;
            }

            switch ($key) {
                case 'closed':
                    $value = (bool)$value;
                    break;
                case 'price':
                case 'cityId':
                    $value = (int)$value;
                    break;
            }

            if (gettype($key) === 'string' && $value === '') {
                $value = null;
            }
        }
    }
}
