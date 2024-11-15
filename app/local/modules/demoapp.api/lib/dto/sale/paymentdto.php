<?php

namespace NaturaSiberica\Api\DTO\Sale;

use NaturaSiberica\Api\DTO\AbstractDTO;

class PaymentDTO extends AbstractDTO
{
    public ?int $id = null;
    public string $name;
    public ?int $sum = null;
    public ?string $logo = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     *
     * @return PaymentDTO
     */
    public function setId(?int $id): PaymentDTO
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
     * @return PaymentDTO
     */
    public function setName(string $name): PaymentDTO
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return float
     */
    public function getSum(): ?float
    {
        return $this->sum;
    }

    /**
     * @param float|null $sum
     *
     * @return PaymentDTO
     */
    public function setSum(?float $sum): PaymentDTO
    {
        $this->sum = $sum;
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
     * @return PaymentDTO
     */
    public function setLogo(?string $logo): PaymentDTO
    {
        $this->logo = $logo;
        return $this;
    }



    /**
     * @inheritDoc
     */
    protected function requiredParameters(): array
    {
        return ['NAME'];
    }
}
