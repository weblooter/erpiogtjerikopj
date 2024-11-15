<?php

namespace NaturaSiberica\Api\Validators;

use NaturaSiberica\Api\Exceptions\DTOException;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;

class DTOValidator extends AbstractValidator
{
    /**
     * @throws ServiceException
     */
    public static function assertRequiredParameters(array $data, array $requiredParameters): bool
    {
        foreach ($requiredParameters as $parameter) {
            self::assertNotEmpty($parameter, $data);
        }

        return true;
    }

    /**
     * @throws DTOException
     */
    public static function assertIdNotNull(DTOInterface $object): bool
    {
        self::assertPrimaryNotNull('ID', 'getId', $object);

        return true;
    }

    /**
     * @param string       $property
     * @param DTOInterface $dto
     *
     * @return void
     * @throws DTOException
     */
    public static function assertPropertyNotNull(string $property, DTOInterface $dto)
    {
        if (!property_exists($dto, $property) || $dto->{$property} === null) {
            throw new DTOException($property);
        }
    }

    /**
     * @throws DTOException
     */
    public static function assertPrimaryNotNull(string $field, string $method, DTOInterface $object): bool
    {
        if (!method_exists($object, $method) || call_user_func([$object, $method]) === null) {
            throw new DTOException($field);
        }
        return true;
    }
}
