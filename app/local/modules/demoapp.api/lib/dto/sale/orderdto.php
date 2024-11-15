<?php

namespace NaturaSiberica\Api\DTO\Sale;

use Bitrix\Main\Type\DateTime;
use NaturaSiberica\Api\DTO\DTO;
use Spatie\DataTransferObject\DataTransferObject;

class OrderDTO extends DataTransferObject
{
    public ?int $id = null;

    public int     $userId;
    public string  $statusId;
    public ?int    $basePrice     = null;
    public ?int    $price         = null;
    public ?int    $discountPrice = null;
    public ?int    $totalPrice    = null;
    public ?string $comments      = null;
    public bool    $canceled      = false;
    public bool    $paid          = false;
    public int     $paidBonuses   = 0;
    public ?string $coupon        = null;
    public int     $paidCoupon    = 0;
    public ?string $dateInsert    = null;
    public ?string $dateUpdate    = null;
    public ?array  $paySystem     = null;
    public ?array  $delivery      = null;

    public CartDTO $cart;
}
