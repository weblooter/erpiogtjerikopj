<?php

namespace NaturaSiberica\Api\Interfaces\Factories;

use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Interfaces\Repositories\RepositoryInterface;
use NaturaSiberica\Api\Interfaces\Services\ServiceInterface;

interface FactoryInterface
{
    /**
     * @return DTOInterface
     */
    public static function createDTO(): DTOInterface;
}
