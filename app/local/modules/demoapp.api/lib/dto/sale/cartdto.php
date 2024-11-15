<?php

namespace NaturaSiberica\Api\DTO\Sale;

use NaturaSiberica\Api\Collections\Sale\CartItemCollection;
use NaturaSiberica\Api\DTO\DTO;
use NaturaSiberica\Api\Exceptions\ServiceException;
use ReflectionException;
use Spatie\DataTransferObject\DataTransferObject;

class CartDTO extends DataTransferObject
{
    public int $fuserId;
    public int $totalPrice = 0;
    public int $discountPrice = 0;
    public array $items = [];
}
