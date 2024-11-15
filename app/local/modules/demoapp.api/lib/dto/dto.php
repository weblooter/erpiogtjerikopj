<?php

namespace NaturaSiberica\Api\DTO;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use NaturaSiberica\Api\Collections\Collection;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Interfaces\Services\TokenServiceInterface;
use NaturaSiberica\Api\Traits\NormalizerTrait;
use NaturaSiberica\Api\Validators\DTOValidator;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Spatie\DataTransferObject\DataTransferObject;

abstract class DTO extends DataTransferObject
{
    public function toJson()
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
