<?php

namespace NaturaSiberica\Api\Interfaces;

use Bitrix\Main\Result;
use NaturaSiberica\Api\DTO\NotificationDTO;

interface NotificationInterface
{
    /**
     * Высылает одноразовый код подтверждения на переданный номер телефона
     *
     * @param NotificationDTO $dto
     *
     * @return Result
     */
    public function sendCode(NotificationDTO $dto): Result;
}
