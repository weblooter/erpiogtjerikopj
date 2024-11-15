<?php

namespace NaturaSiberica\Api\Repositories\Sale;

use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Exception;
use NaturaSiberica\Api\DTO\Sale\DeliveryDataDTO;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Repositories\CityRepository;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;

class DeliveryDataRepository implements ConstantEntityInterface
{
    use HighloadBlockTrait;

    const DELIVERY_TYPE_CODE_ORDER_PICKUP_POINT = 'PVZ';
    const DELIVERY_TYPE_CODE_COURIER            = 'CUR';
    const DELIVERY_TYPE_CODE_POST               = 'POST';

    protected ?int $cityId = null;

    protected array $select = [
        'id',
        'code'         => 'UF_NOM_CODE',
        'name'         => 'UF_NAME',
        'price'        => 'UF_PRICE',
        'cityId'       => 'C.ID',
        'city'         => 'UF_CITY',
        'deliveryTime' => 'UF_DELIVERY_TIME',
        'type'         => 'UF_DELIVERY_TYPE',
        'typeCode'     => 'UF_DELIVERY_TYPECODE',
        'description'  => 'UF_DESCRIPTION',
        'phone'        => 'UF_PHONE',
        'latitude'     => 'UF_LATITUDE',
        'longitude'    => 'UF_LONGITUDE',
        'closed'       => 'UF_CLOSE'
    ];

    protected string $cityMlkIdFieldName = 'UF_MLK_CITY_ID_STRING';

    protected array $collection = [];

    private Query $query;

    private array $filter = [
        'closed' => false,
        '!code' => false,
        '!name' => false,
        '!city' => false,
        '!type' => false,
    ];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->setQuery();
    }

    /**
     * @return void
     * @throws Exception
     */
    private function setQuery()
    {
        $this->query = $this->getHlEntityByEntityName(self::HLBLOCK_MLK_DELIVERY_DATA)->getDataClass()::query();
        $this->query->setCacheTtl(ModuleInterface::ONE_DAY_IN_SECONDS);
        $this->query->cacheJoins(true);
        $this->query->setFilter($this->filter);
        $this->query->setSelect($this->select);
        $this->query->registerRuntimeField(
            new Reference(
                'C', $this->getHlEntityByEntityName(self::HLBLOCK_CITY)->getDataClass(), Join::on('this.UF_CITY', 'ref.UF_NAME')
            )
        );
    }

    /**
     * @param int|null $cityId
     * @return void
     */
    public function setCityId(?int $cityId = null): void
    {
        $this->cityId = $cityId;

        if ($cityId !== null) {
            $this->filter['=cityId'] = $cityId;
            $this->query->setFilter($this->filter);
        }
    }

    /**
     * @param string|null $code
     *
     * @return DeliveryDataDTO|void
     *
     * @throws Exception
     */
    public function findByCode(string $code = null)
    {
        if (empty($code)) {
            return;
        }

        $rows = $this->find(['=code' => $code]);

        if (empty($rows)) {
            return;
        }

        $row = $rows[0];
        return new DeliveryDataDTO($row);
    }

    /**
     * @param array $filter
     * @param array $select
     * @param array $order
     * @param array $group
     * @param int   $limit
     * @param int   $offset
     *
     * @return array
     * @throws Exception
     */
    public function find(array $filter = [], array $select = [], array $order = [], array $group = [], int $limit = 0, int $offset = 0): array
    {
        $this->query->setFilter(array_merge($this->filter, $filter));
        $this->query->setSelect(array_merge($this->select, $select));
        $this->query->setOrder($order);
        $this->query->setGroup($group);
        $this->query->setLimit($limit);
        $this->query->setOffset($offset);

        return $this->query->fetchAll();
    }

    /**
     * @param string $typeCode
     *
     * @return array
     * @throws Exception
     */
    public function findByTypeCode(string $typeCode): array
    {
        $filter = ['=typeCode' => $typeCode];
        $rows   = $this->find($filter);
        $this->prepareCollection($rows);

        return $this->collection;
    }

    private function prepareCollection(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $object                                = new DeliveryDataDTO($row);
            $this->collection[$object->typeCode][] = $object;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getCollection(): array
    {
        $city = (new CityRepository())->getById($this->cityId);
        $cityMlkId = $city->get($this->cityMlkIdFieldName);
        if (!$cityMlkId) {
            $elems = $this->query->fetchAll();
        } else {
            $query = $this->getQueryWithCityIdJoin();
            $elems = $query->fetchAll();
        }
        $this->prepareCollection($elems);
        return $this->collection;
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    private function getQueryWithCityIdJoin()
    {
        $query = $this->getHlEntityByEntityName(self::HLBLOCK_MLK_DELIVERY_DATA)->getDataClass()::query();
        $query->setCacheTtl(ModuleInterface::ONE_DAY_IN_SECONDS);
        $query->cacheJoins(true);
        $query->setFilter($this->filter);
        $query->setSelect($this->select);
        $query->registerRuntimeField(
            new Reference(
                'C', $this->getHlEntityByEntityName(self::HLBLOCK_CITY)->getDataClass(), Join::on('this.UF_CITY_ID', 'ref.UF_MLK_CITY_ID_STRING')
            )
        );
        return $query;
    }
}
