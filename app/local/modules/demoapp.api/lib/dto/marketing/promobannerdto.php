<?php

namespace NaturaSiberica\Api\DTO\Marketing;

use NaturaSiberica\Api\DTO\AbstractDTO;

class PromoBannerDTO extends AbstractDTO
{

    private int    $id;
    private string $name;
    private string $code;
    private string $position;
    private string $picture;
    private string $description;
    private int $categoryId;
    private int $productId;
    private int $actionId;
    private int $brandId;
    private int $seriesId;

    public function __construct(array $attributes)
    {
        parent::__construct($attributes);
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @param string $position
     */
    public function setPosition(string $position): void
    {
        $this->position = $position;
    }

    /**
     * @param string $picture
     */
    public function setPicture(string $picture): void
    {
        $this->picture = $picture;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @param int $categoryId
     */
    public function setCategoryId(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @param int $productId
     */
    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @param int $actionId
     */
    public function setActionId(int $actionId): void
    {
        $this->actionId = $actionId;
    }

    /**
     * @param int $brandId
     */
    public function setBrandId(int $brandId): void
    {
        $this->brandId = $brandId;
    }

    /**
     * @param int $seriesId
     */
    public function setSeriesId(int $seriesId): void
    {
        $this->seriesId = $seriesId;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getPosition(): string
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getPicture(): string
    {
        return $this->picture;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * @return int
     */
    public function getActionId(): int
    {
        return $this->actionId;
    }

    /**
     * @return int
     */
    public function getBrandId(): int
    {
        return $this->brandId;
    }

    /**
     * @return int
     */
    public function getSeriesId(): int
    {
        return $this->seriesId;
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
