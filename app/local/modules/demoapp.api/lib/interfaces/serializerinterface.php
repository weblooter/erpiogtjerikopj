<?php

namespace NaturaSiberica\Api\Interfaces;

use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;

interface SerializerInterface
{
    /**
     * Преобразование объекта в массив
     *
     * @param bool              $keyToUpper Приведение ключей массива к верхнему регистру
     * @param DTOInterface|null $object
     *
     * @return array
     */
    public function toArray(bool $keyToUpper = false, DTOInterface $object = null): array;
}
