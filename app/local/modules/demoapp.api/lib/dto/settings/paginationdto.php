<?php

namespace NaturaSiberica\Api\DTO\Settings;

use NaturaSiberica\Api\DTO\AbstractDTO;


class PaginationDTO extends AbstractDTO
{
    /**
     * @var int Количество запрошенных элементов
     */
    private int $limit;
    /**
     * @var int Количество пропущенных элементов
     */
    private int $offset;
    /**
     * @var int Общее количество элементов
     */
    private int $total;

    /**
     * @param array $attributes
     *
     * @throws \NaturaSiberica\Api\Exceptions\ServiceException
     * @throws \ReflectionException
     */
    public function __construct(array $attributes)
    {
        parent::__construct($attributes);
    }

    /**
     * @param int $value
     *
     * @return void
     */
    public function setLimit(int $value)
    {
        $this->limit = $value;
    }

    /**
     * @param int $value
     *
     * @return void
     */
    public function setOffset(int $value)
    {
        $this->offset = $value;
    }

    /**
     * @param int $value
     *
     * @return void
     */
    public function setTotal(int $value)
    {
        $this->total = $value;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return array
     */
    protected function requiredParameters(): array
    {
        // TODO: Implement requiredParameters() method.
        return [];
    }
}
