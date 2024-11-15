<?php
namespace NaturaSiberica\Api\DTO\Catalog;

use NaturaSiberica\Api\DTO\AbstractDTO;
use NaturaSiberica\Api\DTO\Settings\SeoDataElementDTO;

class CategoryDTO extends AbstractDTO
{
    private int    $id = 0;
    private string $code = '';
    private string $name = '';
    private int    $parentId = 0;
    private string $picture = '';
    private bool  $isNew = false;
    private string $description = '';
    private SeoDataElementDTO $seoData;

    public function __construct(array $attributes)
    {
        parent::__construct($attributes);
    }

    /**
     * @param int|null $value
     *
     * @return void
     */
    public function setId(int $value): void
    {
        $this->id = $value;
    }
    public function setIsNew(int $value): void
    {
        $this->isNew = ((int)$value > 0);
    }
    public function getIsNew(): bool
    {
        return $this->isNew;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setCode(string $value): void
    {
        $this->code = $value;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setName(string $value): void
    {
        $this->name = $value;
    }

    /**
     * @param int|null $value
     *
     * @return void
     */
    public function setParentId(?int $value): void
    {
        $this->parentId = intval($value);
    }

    /**
     * @param string|null $value
     *
     * @return void
     */
    public function setPicture(?string $value): void
    {
        $this->picture = $value;
    }


    /**
     * @param string $value
     *
     * @return void
     */
    public function setDescription(?string $value): void
    {
        $this->description = ($value ?: '');
    }

    /**
     * @param object $value
     *
     * @return void
     * @throws \NaturaSiberica\Api\Exceptions\ServiceException
     * @throws \ReflectionException
     */
    public function setSeoData(?object $value): void
    {
        $this->seoData = new SeoDataElementDTO($value->toArray());
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
    public function getCode(): string
    {
        return $this->code;
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
    public function getParentId(): int
    {
        return $this->parentId;
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
     * @return array
     * @throws \ReflectionException
     */
    public function getSeoData(): array
    {
        return $this->seoData->toArray();
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
