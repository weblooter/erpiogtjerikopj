<?php

namespace NaturaSiberica\Api\DTO\User;

use NaturaSiberica\Api\DTO\AbstractDTO;

final class FuserDTO extends AbstractDTO
{
    public int $id;
    public ?int $userId = null;

    protected function requiredParameters(): array
    {
        return ['ID'];
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * @param int|null $userId
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }
}
