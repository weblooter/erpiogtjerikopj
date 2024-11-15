<?php

namespace NaturaSiberica\Api\Interfaces\Repositories;

use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;

interface RepositoryInterface
{
    const COUNT_ONE = 1;

    public function all(bool $toArray = false): array;

    public function one(bool $toArray = false);

    public function findBy(string $field, $value);

    public function create();

    public function update(DTOInterface $object): bool;

    public function delete(DTOInterface $object): bool;
}
