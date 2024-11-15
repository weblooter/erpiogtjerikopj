<?php

namespace NaturaSiberica\Api\Services\Sale;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\Exceptions\RepositoryException;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Repositories\Sale\StoreRepository;
use ReflectionException;

class StoreService
{
    private StoreRepository $storeRepository;

    public function __construct()
    {
        $this->storeRepository = new StoreRepository();
    }

    /**
     * @param int $cityId
     *
     * @return array
     *
     * @throws ArgumentNullException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws RepositoryException
     * @throws ServiceException
     * @throws ReflectionException
     */
    public function getStores(int $cityId): array
    {
        $this->storeRepository->setCityId($cityId);

        return [
            'list' => $this->storeRepository->all()
        ];
    }
}
