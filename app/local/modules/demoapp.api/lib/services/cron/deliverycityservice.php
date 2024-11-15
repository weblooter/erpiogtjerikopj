<?php

namespace NaturaSiberica\Api\Services\Cron;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Tools\Settings\Options;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;

class DeliveryCityService implements ConstantEntityInterface
{
    use HighloadBlockTrait;

    private array $selectFields = [
        'ID',
        'UF_REGION_ID',
        'UF_CITY_ID',
        'UF_CITY',
        'UF_PRICE',
        'UF_DELIVERY_TIME',
        'UF_LATITUDE',
        'UF_LONGITUDE',
    ];

    public function getCitiesEntity()
    {
        return $this->getHlEntityByEntityName(self::HLBLOCK_CITY)->getDataClass();
    }

    public function getMlkDeliveryDataEntity()
    {
        return $this->getHlEntityByEntityName(self::HLBLOCK_MLK_DELIVERY_DATA)->getDataClass();
    }

    /**
     * @return array
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getCitiesList(): array
    {
        $cities = $this->getCitiesEntity()::getList([
            'filter' => ['UF_HIDE_FROM_LIST' => false],
            'select' => ['UF_NAME'],
            'order'  => ['UF_NAME' => 'ASC'],
        ])->fetchCollection();

        return $cities->fill('UF_NAME');
    }

    private function getFilter(): array
    {
        return [
            '!UF_CITY'      => $this->getCitiesList(),
            '!UF_REGION_ID' => Options::getDeliveryExcludedCountriesIds(),
        ];
    }

    public function getData(): array
    {
        $data    = [];
        $options = [
            'filter' => $this->getFilter(),
            'select' => $this->selectFields,
            'group'  => 'UF_CITY_ID',
        ];

        $list = $this->getMlkDeliveryDataEntity()::getList($options)->fetchAll();

        foreach ($list as $item) {
            $regionId = $item['UF_REGION_ID'];

            $data[$regionId] = [
                'UF_MLK_CITY_ID'    => (int)$item['ID'],
                'UF_NAME'           => $item['UF_CITY'],
                'UF_DELIVERY_PRICE' => $item['UF_PRICE'],
                'UF_DELIVERY_TIME'  => $item['UF_DELIVERY_TIME'],
                'UF_SORT'           => (int)$item['UF_SORT'],
                'UF_LATITUDE'       => $item['UF_LATITUDE'],
                'UF_LONGITUDE'      => $item['UF_LONGITUDE'],
            ];
        }

        return $data;
    }

    public function run(): array
    {
        $results = [];
        $data    = $this->getData();

        while (! empty($data)) {
            foreach ($data as $item) {
                if ($this->isCityExists($item['UF_NAME'])) {
                    continue;
                }

                $result = $this->getCitiesEntity()::add($item);

                $status = $result->isSuccess() ? 'cities' : 'errors';
                $value  = $result->isSuccess() ? $result->getId() : $result->getErrorMessages();

                $results[$status][] = $value;
            }

            $data = $this->getData();
            usleep(500);
        }

        return $results;
    }

    public function isCityExists(string $cityName): bool
    {
        $city = $this->getCitiesEntity()::getList([
            'filter' => ['=UF_NAME' => $cityName],
            'select' => ['ID', 'UF_NAME'],
        ])->fetchAll();

        return ! empty($city);
    }
}
