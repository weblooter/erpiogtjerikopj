<?php

namespace NaturaSiberica\Api\Repositories;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\DTO\CityDTO;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;

Loader::includeModule('highloadblock');

class CityRepository implements ConstantEntityInterface
{
    use HighloadBlockTrait;

    protected array $select = [
        'ID',
        'name'              => 'UF_NAME',
        'delivery_price'    => 'UF_DELIVERY_PRICE',
        'sort'              => 'UF_SORT',
        'latitude'          => 'UF_LATITUDE',
        'longitude'         => 'UF_LONGITUDE',
        'deliveryTime'      => 'UF_DELIVERY_TIME',
        'prepositionalCase' => 'UF_PREPOSITIONAL_CASE',
        'mlkCityId'         => 'UF_MLK_CITY_ID_STRING'
    ];

    protected Query $query;

    /**
     * @var CityDTO[]
     */
    protected array $collection = [];

    public function __construct()
    {
        $this->setQuery();
    }

    protected function setQuery(): CityRepository
    {
        $this->query = $this->getHlEntityByEntityName(self::HLBLOCK_CITY)->getDataClass()::query();
        $this->query->addFilter('UF_HIDE_FROM_LIST', false)->setSelect($this->select)->addOrder('sort')->addOrder('name');

        return $this;
    }

    protected function prepareCollection(): CityRepository
    {
        $cities = $this->query->fetchAll();

        if (empty($cities)) {
            return $this;
        }

        foreach ($cities as $city) {
            $this->collection[] = new CityDTO([
                'id'            => (int)$city['ID'],
                'name'          => $city['name'],
                'deliveryPrice' => (int)$city['delivery_price'],
                'deliveryTime'  => $city['deliveryTime'],
                'sort'          => (int)$city['sort'],
                'latitude'      => $city['latitude'],
                'longitude'     => $city['longitude'],
                'cases'         => [
                    'prepositional' => $city['prepositionalCase'],
                ],

            ]);
        }

        return $this;
    }

    public function all(): array
    {
        if (empty($this->collection)) {
            $this->prepareCollection();
        }

        return $this->collection;
    }

    public static function getEntity()
    {
        return (new static())->getHlEntityByEntityName(self::HLBLOCK_CITY)->getDataClass();
    }

    /**
     * @param $cityId
     *
     * @return bool
     * @throws SystemException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     */
    public static function check($cityId): bool
    {
        $city = self::getEntity()::getById($cityId)->fetch();

        return $city !== false;
    }

    /**
     * @param int $id
     *
     * @return EntityObject|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getById(int $id): ?EntityObject
    {
        return self::getEntity()::getById($id)->fetchObject();
    }
}
