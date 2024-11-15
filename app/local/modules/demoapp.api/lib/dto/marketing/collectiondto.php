<?php

namespace NaturaSiberica\Api\DTO\Marketing;

use CFile;
use NaturaSiberica\Api\DTO\AbstractDTO;
use NaturaSiberica\Api\DTO\Settings\SeoDataElementDTO;

class CollectionDTO extends AbstractDTO
{
    private int     $id;
    private string  $code;
    private string  $name;
    private ?string $image        = null;
    private ?string $previewImage = null;
    private string  $description;
    private array   $productList  = [];
    private array   $seoData      = [];

    public function __construct(array $attributes)
    {
        parent::__construct($attributes);
    }

    public function setId(int $value)
    {
        $this->id = $value;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setCode(string $value)
    {
        $this->code = $value;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setName(string $value)
    {
        $this->name = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setPicture(string $value)
    {
        $this->picture = $value;
    }

    public function getPicture(): string
    {
        return $this->picture;
    }

    public function setDescription(string $value)
    {
        $this->description = $value;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setProductList(array $value)
    {
        $this->productList = $value;
    }

    public function getProductList(): array
    {
        return $this->productList;
    }

    public function setSeoData(array $value): void
    {
        $data          = new SeoDataElementDTO($value);
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
