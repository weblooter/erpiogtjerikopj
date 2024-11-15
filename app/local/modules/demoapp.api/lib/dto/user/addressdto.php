<?php

namespace NaturaSiberica\Api\DTO\User;

use NaturaSiberica\Api\DTO\AbstractDTO;
use NaturaSiberica\Api\DTO\DTO;
use NaturaSiberica\Api\Entities\UserAddressTable;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Traits\NormalizerTrait;
use Spatie\DataTransferObject\DataTransferObject;

class AddressDTO extends DataTransferObject implements DTOInterface
{
    use NormalizerTrait;

    public ?int $id = null;

    public int $userId;

    public string $fiasId;
    public string $name;
    public string $region;
    public string $city;
    public string $street;
    public string $houseNumber;

    public ?string $fullAddress = null;
    public ?string $flat        = null;
    public ?string $entrance    = null;
    public ?string $floor       = null;
    public ?string $doorPhone   = null;

    public string $latitude;
    public string $longitude;

    public bool $default      = false;
    public bool $privateHouse = false;

    public function __construct(array $parameters = [])
    {
        if (empty($parameters['name'])) {
            $parameters['name'] = UserAddressTable::toString(null, $parameters);
        }

        $parameters['fullAddress'] = UserAddressTable::toString(null, $parameters);

        parent::__construct($parameters);
    }

    public function convertToBitrixFormat(bool $excludeId = false)
    {
        $dto = $this;
        if ($excludeId) {
            $dto = $dto->except('id');
        }

        $result = [];

        foreach ($dto->toArray() as $key => $value) {
            $field          = $this->convertCamelToSnake($key);
            $result[$field] = $value;
        }

        return $result;
    }

    public function modify(array $fields): DTOInterface
    {
        $rc = new \ReflectionClass($this);
        foreach ($fields as $key => $value) {
            if (in_array($key, $this->readonlyFields())) {
                continue;
            }

            if ($rc->hasProperty($key)) {
                $property = $rc->getProperty($key);
                $type     = $property->getType()->getName();

                if ($type === 'int' && gettype($value) === 'string') {
                    $value = (int)$value;
                }

                $rc->getProperty($key)->setValue($this, $value);
            }
        }

        return $this;
    }

    public function readonlyFields()
    {
        return [
            'id',
            'userId',
            'fiasId',
        ];
    }

    /**
     * Массив с обязательными параметрами для валидации данных
     *
     * @return array
     */
    protected function requiredParameters(): array
    {
        return ['USER_ID', 'FIAS_ID', 'REGION', 'CITY', 'STREET', 'HOUSE_NUMBER', 'LATITUDE', 'LONGITUDE'];
    }
}
