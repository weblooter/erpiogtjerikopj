<?php

namespace NaturaSiberica\Api\Factories\Sale;

use Bitrix\Sale\Fuser;
use Bitrix\Sale\Payment;
use DateTime;
use Exception;
use NaturaSiberica\Api\DTO\Sale\OrderDTO;
use NaturaSiberica\Api\Helpers\Sale\PriceHelper;
use NaturaSiberica\Api\Helpers\User\AddressHelper;
use NaturaSiberica\Api\Repositories\Sale\OrderRepository;
use NaturaSiberica\Api\Services\Catalog\ProductService;
use ReflectionException;

class OrderFactory
{

    /**
     * @param OrderRepository $orderRepository
     *
     * @return OrderDTO
     * @throws Exception
     */
    public static function createDTO(OrderRepository $orderRepository): OrderDTO
    {
        $attrs = self::createItem($orderRepository);
        return new OrderDTO($attrs);
    }

    /**
     * @param OrderRepository $orderRepository
     *
     * @return array
     *
     * @throws Exception
     */
    protected static function createItem(OrderRepository $orderRepository): array
    {
        $order              = $orderRepository->getOrder();
        $cartRepository     = $orderRepository->getCartRepository();
        $deliveryRepository = $orderRepository->getDeliveryRepository();

        $fuserId = Fuser::getIdByUserId($order->getUserId());

        $cartRepository->setFuserId($fuserId)->setOrder($order)->setBasket($order->getBasket());

        $paymentCollection = $order->getPaymentCollection();

        /**
         * @var Payment $payment
         */
        $payment              = $paymentCollection[0];
        $propertyMindboxBonus = $order->getPropertyCollection()->getItemByOrderPropertyCode('MINDBOX_BONUS');
        $propertyMindboxCoupon = $order->getPropertyCollection()->getItemByOrderPropertyCode('MINDBOX_PROMO_CODE');
        $propertyMindboxCouponValue = $order->getPropertyCollection()->getItemByOrderPropertyCode('MINDBOX_PROMO_VALUE');

        $attrs = [
            'id'            => $order->getId(),
            'userId'        => (int)$order->getField('USER_ID'),
            'statusId'      => $order->getField('STATUS_ID'),
            'dateInsert'    => $order->getDateInsert()->format(DateTime::ISO8601),
            'dateUpdate'    => $order->getField('DATE_UPDATE')->format(DateTime::ISO8601),
            'basePrice'     => PriceHelper::format($order->getBasket()->getBasePrice()),
            'price'         => PriceHelper::format($order->getBasket()->getPrice()),
            'discountPrice' => PriceHelper::format($order->getBasket()->getBasePrice() - $order->getBasket()->getPrice()),
            'totalPrice'    => PriceHelper::format($order->getBasket()->getPrice() + $order->getDeliveryPrice()),
            'paid'          => $order->getPaymentCollection()->isPaid(),
            'paidBonuses'   => (int)$propertyMindboxBonus->getValue(),
            'coupon'        => $propertyMindboxCoupon->getValue(),
            'paidCoupon'    => PriceHelper::format($propertyMindboxCouponValue->getValue()),
            'canceled'      => $order->isCanceled(),
            'comments'      => $order->getField('USER_DESCRIPTION'),
        ];

        if (! empty($payment)) {
            $attrs['paySystem'] = [
                'id'   => $payment->getPaymentSystemId(),
                'name' => $payment->getPaymentSystemName(),
                'code' => $payment->getPaySystem()->getField('CODE'),
            ];
        }

        $deliveryId = $order->getField('DELIVERY_ID');

        if (! empty($deliveryId)) {
            $propertyCityId = $order->getPropertyCollection()->getItemByOrderPropertyCode('CITY_ID');
            $cityId         = $propertyCityId->getValue();

            if (! empty($cityId)) {
                $deliveryRepository->setCityId($cityId);
            }

            $deliveryDTO = $deliveryRepository->findById($order, $deliveryId);

            $attrs['delivery'] = [
                'typeCode' => $deliveryDTO->typeCode,
                'name'     => $deliveryDTO->name,
                'price'    => PriceHelper::format($order->getDeliveryPrice()),
                'address'  => ! empty($deliveryDTO) ? AddressHelper::getAddress(
                    $order->getPropertyCollection(),
                    $deliveryDTO->typeCode
                ) : null,
            ];
        }

        ProductService::$needDiscontinued = true;
        $attrs['cart'] = $cartRepository->get()->except('fuserId');

        return $attrs;
    }
}
