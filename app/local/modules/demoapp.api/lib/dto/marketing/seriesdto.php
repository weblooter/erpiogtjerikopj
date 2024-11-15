<?php

namespace NaturaSiberica\Api\DTO\Marketing;

use CFile;
use NaturaSiberica\Api\DTO\AbstractDTO;
use NaturaSiberica\Api\DTO\Content\VideoDTO;

class SeriesDTO extends AbstractDTO
{
    public int       $id;
    public int       $brandId;
    public string    $code;
    public string    $name;
    public string    $previewText;
    public string    $detailText;
    public string    $picture;
    public string    $icon;
    public ?VideoDTO $video = null;

    public function __construct(array $attributes)
    {
        parent::__construct($attributes);
    }

    /**
     * @param int $value
     *
     * @return void
     */
    public function setId(int $value): void
    {
        $this->id = $value;
    }

    /**
     * @param int $value
     *
     * @return void
     */
    public function setBrandId(int $value): void
    {
        $this->brandId = $value;
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
     * @param string $value
     *
     * @return void
     */
    public function setPreviewText(string $value): void
    {
        $this->previewText = $value;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setDetailText(string $value): void
    {
        $this->detailText = $value;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setPicture(string $value): void
    {
        $this->picture = $value;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setIcon(string $value): void
    {
        $this->icon = $value;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getBrandId(): int
    {
        return $this->brandId;
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
    public function getPreviewText(): string
    {
        return $this->previewText;
    }

    /**
     * @return string
     */
    public function getDetailText(): string
    {
        return $this->detailText;
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
    public function getIcon(): string
    {
        return $this->icon;
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
