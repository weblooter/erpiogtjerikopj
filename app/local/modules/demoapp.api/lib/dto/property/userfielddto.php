<?php

namespace NaturaSiberica\Api\DTO\Property;

use NaturaSiberica\Api\DTO\AbstractDTO;

class UserFieldDTO extends AbstractDTO
{
    public int $id;
    public int $fieldId;
    public string $xmlId;
    public string $value;

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
     * @return UserFieldDTO
     */
    public function setId(int $id): UserFieldDTO
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getFieldId(): int
    {
        return $this->fieldId;
    }

    /**
     * @param int $fieldId
     *
     * @return UserFieldDTO
     */
    public function setFieldId(int $fieldId): UserFieldDTO
    {
        $this->fieldId = $fieldId;
        return $this;
    }

    /**
     * @return string
     */
    public function getXmlId(): string
    {
        return $this->xmlId;
    }

    /**
     * @param string $xmlId
     *
     * @return UserFieldDTO
     */
    public function setXmlId(string $xmlId): UserFieldDTO
    {
        $this->xmlId = $xmlId;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return UserFieldDTO
     */
    public function setValue(string $value): UserFieldDTO
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function requiredParameters(): array
    {
        return [];
    }
}
