<?php

namespace NaturaSiberica\Api\DTO\Sale;

use NaturaSiberica\Api\DTO\AbstractDTO;

class DeliveryDTO extends AbstractDTO
{
    public int $id;
    public string $name;
    public ?int $price = null;
    public ?bool $allowed = null;
    public ?string $logo = null;
    /**
     * @inheritDoc
     */
    protected function requiredParameters(): array
    {
        return ['ID', 'NAME'];
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
     *
     * @return DeliveryDTO
     */
    public function setId(int $id): DeliveryDTO
    {
        $this->id = $id;
        return $this;
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
     *
     * @return DeliveryDTO
     */
    public function setName(string $name): DeliveryDTO
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPrice(): ?int
    {
        return $this->price;
    }

    /**
     * @param int|null $price
     *
     * @return DeliveryDTO
     */
    public function setPrice(?int $price): DeliveryDTO
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getAllowed(): ?bool
    {
        return $this->allowed;
    }

    /**
     * @param bool|null $allowed
     *
     * @return DeliveryDTO
     */
    public function setAllowed(?bool $allowed): DeliveryDTO
    {
        $this->allowed = $allowed;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }

    /**
     * @param string|null $logo
     *
     * @return DeliveryDTO
     */
    public function setLogo(?string $logo): DeliveryDTO
    {
        $this->logo = $logo;
        return $this;
    }
}
