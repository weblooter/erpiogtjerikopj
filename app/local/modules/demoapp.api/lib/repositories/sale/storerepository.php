<?php

namespace NaturaSiberica\Api\Repositories\Sale;

use Bitrix\Catalog\StoreTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\DTO\Sale\StoreDTO;
use NaturaSiberica\Api\Exceptions\RepositoryException;
use NaturaSiberica\Api\Factories\Sale\StoreFactory;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Repositories\CityRepository;
use NaturaSiberica\Api\Repositories\Repository;

Loader::includeModule('highloadblock');
Loader::includeModule('catalog');

class StoreRepository extends Repository implements ModuleInterface
{
    const HL_ENTITY_NAME = 'Stores';
    protected array $select = [
        'ID',
        'PHONE',
        'CITY_ID'            => 'UF_CITY',
        'NAME'               => 'TITLE',
        'CITY_NAME'          => 'C.UF_NAME',
        'CITY_SORT'          => 'C.UF_SORT',
        'CITY_LAT'           => 'C.UF_LATITUDE',
        'CITY_LON'           => 'C.UF_LONGITUDE',
        'DELIVERY_PRICE'     => 'C.UF_DELIVERY_PRICE',
        'DELIVERY_TIME'      => 'C.UF_DELIVERY_TIME',
        'PREPOSITIONAL_CASE' => 'C.UF_PREPOSITIONAL_CASE',
        'ADDRESS'            => 'ADDRESS',
        'LATITUDE'           => 'GPS_N',
        'LONGITUDE'          => 'GPS_S',
        'SCHEDULE'           => 'SCHEDULE',
        'METRO_STATION'      => 'UF_METRO_STATION',
    ];
    private ?int $cityId = null;

    public function __construct(array $options = [])
    {
        $this->setRuntime();
        $this->addOption($options, self::OPTION_DTO_CLASS, StoreDTO::class);
        $this->setQuery(static::getEntity()::query());

        parent::__construct($options);
    }

    /**
     * @return void
     *
     * @throws SystemException
     * @throws ArgumentException
     */
    private function setRuntime()
    {
        $this->runtime = [
            new Reference('C', CityRepository::getEntity(), Join::on('this.UF_CITY', 'ref.ID')),
        ];
    }

    /**
     * @param Query $query
     *
     * @return StoreRepository
     */
    public function setQuery(Query $query): StoreRepository
    {
        $this->query = $query;
        $this->query->addFilter('ACTIVE', 'Y');

        return $this;
    }

    /**
     * @return string
     *
     */
    public static function getEntity(): string
    {
        return StoreTable::class;
    }

    public static function getDefaultStoreId(): int
    {
        return (int)Option::get(static::MODULE_ID, 'default_store');
    }

    /**
     * @return int|null
     */
    public function getCityId(): ?int
    {
        return $this->cityId;
    }

    /**
     * @param int|null $cityId
     *
     * @return StoreRepository
     */
    public function setCityId(?int $cityId = null): StoreRepository
    {
        $this->cityId = $cityId;
        return $this;
    }

    /**
     * @throws RepositoryException
     */
    public function validateRow(array $row): bool
    {
        if (! empty($row)) {
            return true;
        }

        throw new RepositoryException('Empty row');
    }

    /**
     * @return array
     *
     * @throws ArgumentNullException
     * @throws ObjectPropertyException
     * @throws RepositoryException
     * @throws SystemException
     */
    public function getSelectorList(): array
    {
        $result = [];

        /**
         * @var StoreDTO $item
         */
        foreach ($this->all() as $item) {
            $result[$item->id] = sprintf('[%s] %s', $item->id, $item->address);
        }

        return $result;
    }

    /**
     * @param bool $toArray
     *
     * @return array|DTOInterface[]
     *
     * @throws ArgumentNullException
     * @throws RepositoryException
     * @throws SystemException
     * @throws ObjectPropertyException
     */
    public function all(bool $toArray = false): array
    {
        $collection = [];
        if (! empty($this->cityId)) {
            $this->addFilter('CITY_ID', '=', $this->cityId);
        } else {
            $this->query->whereNotNull('CITY_ID');
        }

        $rows = $this->query->fetchAll();

        foreach ($rows as $row) {
            $collection[] = StoreFactory::createDTO($this, $row);
        }

        return $collection;
    }
}
