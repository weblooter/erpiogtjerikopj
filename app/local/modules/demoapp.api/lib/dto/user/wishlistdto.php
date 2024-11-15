<?php

namespace NaturaSiberica\Api\DTO\User;

use Spatie\DataTransferObject\DataTransferObject;

class WishListDTO extends DataTransferObject
{
    public int   $userId;
    public array $list;
}
