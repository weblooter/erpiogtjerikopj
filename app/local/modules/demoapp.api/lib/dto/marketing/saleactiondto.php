<?php

namespace NaturaSiberica\Api\DTO\Marketing;

use CFile;
use NaturaSiberica\Api\DTO\AbstractDTO;
use NaturaSiberica\Api\DTO\Settings\SeoDataElementDTO;

class SaleActionDTO extends AbstractDTO
{
    private int $id;
    private string $code;
    private string $name;
    private string $timeFrom = '';
    private string $timeTo = '';
    private string $picture = '';
    private string $description = '';
    private array $productList = [];
    private array $seoData = [];

    public function __construct(array $attributes)
    {
        parent::__construct($attributes);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getTimeFrom(): string
    {
        return $this->timeFrom;
    }

    /**
     * @param string $timeFrom
     */
    public function setTimeFrom(string $timeFrom): void
    {
        $this->timeFrom = $timeFrom;
    }

    /**
     * @return string
     */
    public function getTimeTo(): string
    {
        return $this->timeTo;
    }

    /**
     * @param string $timeTo
     */
    public function setTimeTo(string $timeTo): void
    {
        $this->timeTo = $timeTo;
    }

    /**
     * @return string
     */
    public function getPicture(): string
    {
        return $this->picture;
    }

    /**
     * @param string $picture
     */
    public function setPicture(string $picture): void
    {
        $this->picture = $picture;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getProductList(): array
    {
        return $this->productList;
    }

    /**
     * @param array $productList
     */
    public function setProductList(array $productList): void
    {
        $this->productList = $productList;
    }

    public function setSeoData(array $value): void
    {
        $data = new SeoDataElementDTO(($value));
        $this->seoData = ($data ? $data->toArray() : []);
    }
    public function getSeoData(): array
    {
        return $this->seoData;
    }


    protected function requiredParameters(): array
    {
        // TODO: Implement requiredParameters() method.
        return [];
    }
}
