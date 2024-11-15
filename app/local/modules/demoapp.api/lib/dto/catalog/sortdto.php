<?php

namespace NaturaSiberica\Api\DTO\Catalog;

use NaturaSiberica\Api\DTO\AbstractDTO;

class SortDTO  extends AbstractDTO
{
    private string $name = '';
    private string $code = '';
    private string $sortBy = '';
    private string $sortOrder = '';

    public function __construct(array $attributes)
    {
        parent::__construct($attributes);
    }

    public function setName(string $value)
    {
        $this->name = $value;
    }
    public function getName(): string
    {
        return $this->name;
    }

    public function setCode(string $value)
    {
        $this->code = $value;
    }
    public function getCode(): string
    {
        return $this->code;
    }

    public function setSortBy(string $value)
    {
        $this->sortBy = $value;
    }
    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function setSortOrder(string $value)
    {
        $this->sortOrder = $value;
    }
    public function getSortOrder(): string
    {
        return $this->sortOrder;
    }


    protected function requiredParameters(): array
    {
        // TODO: Implement requiredParameters() method.
        return [];
    }
}
