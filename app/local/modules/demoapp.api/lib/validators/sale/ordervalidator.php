<?php

namespace NaturaSiberica\Api\Validators\Sale;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\BasketBase;
use Bitrix\Sale\Internals\DeliveryPaySystemTable;
use Bitrix\Sale\Internals\OrderTable;
use Bitrix\Sale\Order;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Validators\Validator;

Loc::loadMessages(__FILE__);
Loc::loadMessages(dirname(__DIR__, 2) . '/repositories/sale/orderrepository.php');
Loc::loadMessages(dirname(__DIR__, 2) . '/services/mindbox/sale/orderservice.php');

class OrderValidator extends Validator
{
    private DeliveryValidator $deliveryValidator;

    public function __construct()
    {
        $this->deliveryValidator = new DeliveryValidator();
        parent::__construct();
    }

    public array $requiredFields = [
        'cityId',
        'paySystemId',
        'delivery',
    ];

    public array $productItemsRequiredFields = [
        'id',
        'article',
        'quantity',
        'basePrice',
        'discountPrice',
    ];

    public function getPropertyTitles(): array
    {
        return [
            'cityId'        => Loc::getMessage('city_id'),
            'paySystemId'   => Loc::getMessage('pay_system_id'),
            'delivery'      => Loc::getMessage('delivery'),
            'deliveryId'    => Loc::getMessage('delivery_id'),
            'code'          => Loc::getMessage('delivery_code'),
            'typeCode'      => Loc::getMessage('delivery_type_code'),
            'price'         => Loc::getMessage('delivery_price'),
            'productItemId' => Loc::getMessage('product_item_id'),
            'article'       => Loc::getMessage('product_article'),
            'quantity'      => Loc::getMessage('product_quantity'),
            'basePrice'     => Loc::getMessage('product_base_price'),
            'discountPrice' => Loc::getMessage('product_discount_price'),
        ];
    }

    /**
     * @param int $userId
     * @param int $orderId
     *
     * @return void
     * @throws Exception
     */
    public function validateUserOrder(int $userId, int $orderId)
    {
        $order = OrderTable::getList([
            'filter' => [
                '=ID'      => $orderId,
                '=USER_ID' => $userId,
            ],
            'select' => ['ID', 'USER_ID'],
        ])->fetchAll();

        if (empty($order)) {
            throw new Exception(Loc::getMessage('error_access_denied'), StatusCodeInterface::STATUS_FORBIDDEN);
        }
    }

    /**
     * @throws Exception
     */
    public function validateProductItems()
    {
        if (empty($this->requestBody['productItems'])) {
            throw new Exception(Loc::getMessage('error_product_items_not_found'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        foreach ($this->requestBody['productItems'] as $item) {
            if (! is_array($item)) {
                throw new Exception(Loc::getMessage('error_type'), StatusCodeInterface::STATUS_BAD_REQUEST);
            }

            foreach ($this->productItemsRequiredFields as $field) {
                if (! array_key_exists($field, $item)) {
                    if ($field === 'id') {
                        $field = 'productItemId';
                    }

                    $this->addError($field);
                }
            }

            $this->throwErrors();
        }
    }

    public function validateCommentsLength()
    {
        $maxCommentLength = 1000;

        if (strlen($this->requestBody['comments']) > $maxCommentLength) {
            throw new Exception(Loc::getMessage('error_comment_max_length', [
                '#limit#' => $maxCommentLength
            ]), StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     */
    public function validateDelivery(): OrderValidator
    {
        $this->deliveryValidator->validate($this->requestBody['delivery']);
        return $this;
    }

    /**
     * @param int        $orderId
     * @param Order|null $order
     *
     * @return void
     * @throws Exception
     */
    public static function validateOrderById(int $orderId, Order $order = null): void
    {
        if (! ($order instanceof Order)) {
            throw new Exception(
                Loc::getMessage(
                    'ERROR_ORDER_NOT_EXISTS',
                    [
                        '#ORDER_ID#' => $orderId,
                    ]
                ), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @throws Exception
     */
    public static function validateCancelledOrder(Order $order)
    {
        if ($order->getField('CANCELED') === 'Y') {
            throw new Exception(
                Loc::getMessage(
                    'ERROR_REPEATED_ORDER_CANCELLATION',
                    [
                        '#ORDER_ID#' => $order->getId(),
                    ]
                ),
                StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @param BasketBase|null $basket
     *
     * @return void
     * @throws Exception
     */
    public static function validateBasketOnNull(?BasketBase $basket = null)
    {
        if ($basket === null) {
            throw new Exception(Loc::getMessage('err_cart_not_found'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param array $basketItems
     *
     * @return void
     * @throws Exception
     */
    public static function validateEmptyBasket(array $basketItems)
    {
        if (empty($basketItems)) {
            throw new Exception(Loc::getMessage('ERROR_CREATE_ORDER_WITH_EMPTY_BASKET'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @throws Exception
     */
    public function validate(array $requestBody)
    {
        $this->setRequestBody($requestBody);
        $this->validateRequiredFields();
        $this->validateDelivery();
        $this->validateCommentsLength();
        $this->throwErrors();
    }

    /**
     * @param Order $order
     * @param int   $userId
     *
     * @return void
     * @throws Exception
     */
    public static function validateOrderByUser(Order $order, int $userId): void
    {
        if ((int)$order->getUserId() !== $userId) {
            throw new Exception(Loc::getMessage('error_access_denied'), StatusCodeInterface::STATUS_FORBIDDEN);
        }
    }

    /**
     * @param int $paySystemId
     * @param int $deliveryId
     *
     * @return $this
     * @throws Exception
     */
    public function validateAllowedPaySystemByDelivery(int $paySystemId, int $deliveryId): OrderValidator
    {
        $ids = $this->getPaySystemIds($deliveryId);

        if (! in_array($paySystemId, $ids)) {
            throw new Exception(
                Loc::getMessage('error_paysystem_denied'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
            );
        }

        return $this;
    }

    private function getPaySystemIds(int $deliveryId): array
    {
        return DeliveryPaySystemTable::getLinks($deliveryId, DeliveryPaySystemTable::ENTITY_TYPE_DELIVERY);
    }
}
