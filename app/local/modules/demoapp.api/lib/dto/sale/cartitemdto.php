<?php

namespace NaturaSiberica\Api\DTO\Sale;

use NaturaSiberica\Api\DTO\DTO;
use Spatie\DataTransferObject\DataTransferObject;

class CartItemDTO extends DataTransferObject
{
    public string $xmlId;
    public int    $offerId;
    public int    $quantity;
    public string $name;
    public int    $basePrice;
    public array  $product       = [];
    public ?int   $discountPrice = null;

    /**
     * @return string[]
     */
    protected function requiredParameters(): array
    {
        return ['PRODUCT_ID', 'NAME', 'QUANTITY'];
    }
}
