<?php

namespace NaturaSiberica\Api\Factories;

use NaturaSiberica\Api\DTO\User\AddressDTO;

class AddressDTOFactory
{
    public static function createFromRequestBody(int $userId, array $body): AddressDTO
    {
        $attrs = [
            'userId'       => $userId,
            'fiasId'       => $body['fiasId'],
            'name'         => $body['name'] ? : null,
            'region'       => $body['region'],
            'city'         => $body['city'],
            'street'       => $body['street'],
            'houseNumber'  => (string)$body['houseNumber'],
            'flat'         => (string)$body['flat'] ? : null,
            'entrance'     => (string)$body['entrance'] ? : null,
            'floor'        => (string)$body['floor'] ? : null,
            'doorPhone'    => (string)$body['doorPhone'] ? : null,
            'latitude'     => (string)$body['latitude'],
            'longitude'    => (string)$body['longitude'],
            'default'      => (bool)$body['default'],
            'privateHouse' => (bool)$body['privateHouse'],
        ];

        return new AddressDTO($attrs);
    }

    public static function createFromBitrixFormat(array $row): AddressDTO
    {
        $attrs = [
            'id'           => (int)$row['id'],
            'userId'       => (int)$row['userId'],
            'fiasId'       => $row['fiasId'],
            'name'         => $row['name'],
            'region'       => $row['region'],
            'city'         => $row['city'],
            'street'       => $row['street'],
            'houseNumber'  => $row['houseNumber'],
            'latitude'     => (string)$row['latitude'],
            'longitude'    => (string)$row['longitude'],
            'default'      => (bool)$row['default'],
            'privateHouse' => (bool)$row['privateHouse'],
        ];

        if ($row['privateHouse'] !== true) {
            $attrs['flat']      = ! empty($row['flat']) ? $row['flat'] : null;
            $attrs['floor']     = ! empty($row['floor']) ? $row['floor'] : null;
            $attrs['entrance']  = ! empty($row['entrance']) ? $row['entrance'] : null;
            $attrs['doorPhone'] = ! empty($row['doorPhone']) ? $row['doorPhone'] : null;
        }

        return new AddressDTO($attrs);
    }
}

