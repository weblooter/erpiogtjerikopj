<?php

namespace NaturaSiberica\Api\Services\UserField;

use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\Exceptions\RepositoryException;
use NaturaSiberica\Api\Interfaces\Services\UserField\ResponseInterface;
use NaturaSiberica\Api\Interfaces\Services\UserField\UserFieldServiceInterface;
use NaturaSiberica\Api\Repositories\UserField\EnumFieldRepository;

class UserFieldService implements UserFieldServiceInterface
{
    private EnumFieldRepository $enumFieldRepository;

    public function __construct()
    {
        $this->enumFieldRepository = new EnumFieldRepository();
    }

    /**
     * @return array
     *
     * @throws ObjectPropertyException
     * @throws RepositoryException
     * @throws SystemException
     */
    public function getSkinTypes(): array
    {
        return $this->enumFieldRepository->findByFieldName('skinType')->toArray();
    }

    /**
     * @return array
     *
     * @throws RepositoryException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getMaritalStatuses(): array
    {
        return  $this->enumFieldRepository->findByFieldName('maritalStatus')->toArray();
    }
}
