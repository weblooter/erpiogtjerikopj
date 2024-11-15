<?php

namespace NaturaSiberica\Api\DTO;

use NaturaSiberica\Api\Interfaces\NotificationInterface;
use Spatie\DataTransferObject\DataTransferObject;

class NotificationDTO extends DataTransferObject
{
    public string $provider;
    public string $dispatch;
    public string $message;
}
