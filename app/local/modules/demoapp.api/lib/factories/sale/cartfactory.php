<?php

namespace NaturaSiberica\Api\Factories\Sale;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Order;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\Sale\CartDTO;
use NaturaSiberica\Api\DTO\Sale\CartItemDTO;
use NaturaSiberica\Api\Exceptions\RepositoryException;
use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Helpers\Sale\PriceHelper;
use NaturaSiberica\Api\Interfaces\Factories\DTOFactoryInterface;
use NaturaSiberica\Api\Repositories\Sale\CartRepository;
use NaturaSiberica\Api\Validators\Sale\CartValidator;
use NaturaSiberica\Api\Validators\User\UserValidator;
use ReflectionException;

Loc::loadMessages(__FILE__);

class CartFactory
{

    /**
     * @param CartRepository $repository
     *
     * @return CartDTO
     * @throws ArgumentNullException
     * @throws Exception
     * @throws RequestBodyException
     */
    public static function createDTO(CartRepository $repository): CartDTO
    {
        UserValidator::validateFuser($repository->getFuserId());

        $basket = $repository->getBasket();
        $discount = $basket->getBasePrice() - $basket->getPrice();

        $attrs = [
            'fuserId'       => $repository->getFuserId(),
            'totalPrice'    => PriceHelper::format($basket->getPrice()),
            'discountPrice' => PriceHelper::format($discount),
        ];

        $basket = $repository->getBasket()->toArray();

        if (empty($basket)) {
            return new CartDTO($attrs);
        }

        $offerIds = $repository->getCartItems(true);
        $products = $repository->getProductOffers($offerIds);

        foreach ($basket as $item) {
            $offerId = (int)$item['PRODUCT_ID'];

            $basePrice     = PriceHelper::format($item['BASE_PRICE']);
            $discountPrice = PriceHelper::format($item['PRICE']);
            if ($item['NAME'] && $products[$offerId]) {
                $productItem      = [
                    'xmlId'            => $item['XML_ID'],
                    'offerId'       => $offerId,
                    'name'          => $item['NAME'],
                    'quantity'      => (int)$item['QUANTITY'],
                    'basePrice'     => $basePrice,
                    'discountPrice' => $discountPrice,
                    'product'       => $products[$offerId],
                ];
                $attrs['items'][] = new CartItemDTO($productItem);
            }
        }

        return new CartDTO($attrs);
    }
}
