<?php

namespace NaturaSiberica\Api\V2\Interfaces\Repositories;

use Spatie\DataTransferObject\DataTransferObject;

interface RepositoryInterface
{
    public function findBy(string $field, $value): RepositoryInterface;

    public function all(): array;

    /**
     * @return DataTransferObject|null
     */
    public function get(): ?DataTransferObject;
}
