<?php

namespace NaturaSiberica\Api\Interfaces\Services\Mindbox\Sale;

interface OrderServiceInterface
{
    const OPERATION_NAME_CALCULATE_AUTHORIZED_CART = 'Website.CalculateAuthorizedCart';
    const RESPONSE_PROCESSING_STATUS_CALCULATED    = 'Calculated';
    const RESPONSE_PROCESSING_STATUS_PRICE_CHANGED = 'PriceHasBeenChanged';
}
