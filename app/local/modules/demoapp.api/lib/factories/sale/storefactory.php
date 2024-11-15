<?php

namespace NaturaSiberica\Api\Factories\Sale;

use Bitrix\Main\ArgumentNullException;
use NaturaSiberica\Api\DTO\CityDTO;
use NaturaSiberica\Api\DTO\Sale\StoreDTO;
use NaturaSiberica\Api\Exceptions\RepositoryException;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Interfaces\Factories\FactoryInterface;
use NaturaSiberica\Api\Repositories\Sale\StoreRepository;
use ReflectionException;

class StoreFactory implements FactoryInterface
{

    /**
     * @param StoreRepository|null $storeRepository
     * @param array|null           $row
     *
     * @return DTOInterface
     * @throws RepositoryException
     */
    public static function createDTO(StoreRepository $storeRepository = null, array $row = null): DTOInterface
    {
        $storeRepository->validateRow($row);

        $cityDTO = new CityDTO([
            'id'            => (int)$row['CITY_ID'],
            'name'          => $row['CITY_NAME'],
            'deliveryPrice' => (int)$row['DELIVERY_PRICE'],
            'deliveryTime'  => $row['DELIVERY_TIME'] ? : null,
            'sort'          => (int)$row['CITY_SORT'],
            'latitude'      => $row['CITY_LAT'],
            'longitude'     => $row['CITY_LON'],
            'cases'         => [
                'prepositional' => $row['PREPOSITIONAL_CASE'],
            ],
        ]);

        $attributes = [
            'id'           => (int)$row['ID'],
            'name'         => $row['NAME'],
            'city'         => $cityDTO,
            'address'      => $row['ADDRESS'],
            'phone'        => $row['PHONE'],
            'latitude'     => $row['LATITUDE'],
            'longitude'    => $row['LONGITUDE'],
            'schedule'     => $row['SCHEDULE'],
            'metroStation' => $row['METRO_STATION'],
        ];

        return new StoreDTO($attributes);
    }
}
