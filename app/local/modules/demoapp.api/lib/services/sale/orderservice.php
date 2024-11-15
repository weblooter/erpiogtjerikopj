<?php

namespace NaturaSiberica\Api\Services\Sale;

use Bitrix\Main\Context;
use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryServiceManager;
use Bitrix\Sale\Internals\EO_PersonType;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem\Manager;
use Bitrix\Sale\PaySystem\Manager as PaymentServiceManager;
use Bitrix\Sale\Result;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Mindbox\Exceptions\MindboxBadRequestException;
use NaturaSiberica\Api\DTO\Sale\CartItemDTO;
use NaturaSiberica\Api\DTO\Sale\OrderDTO;
use NaturaSiberica\Api\DTO\Sale\PaySystemDTO;
use NaturaSiberica\Api\Helpers\Sale\PriceHelper;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface as Constants;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Interfaces\Services\Mindbox\Sale\OrderServiceInterface as MindboxOrderServiceInterface;
use NaturaSiberica\Api\Interfaces\Services\Mindbox\User\ProfileServiceInterface as MindboxProfileServiceInterface;
use NaturaSiberica\Api\Interfaces\Services\Sale\OrderServiceInterface;
use NaturaSiberica\Api\Logger\Logger;
use NaturaSiberica\Api\Repositories\Sale\CartRepository;
use NaturaSiberica\Api\Repositories\Sale\DeliveryRepository;
use NaturaSiberica\Api\Repositories\Sale\OrderRepository;
use NaturaSiberica\Api\Repositories\Sale\PaymentRepository;
use NaturaSiberica\Api\Repositories\Sale\StatusRepository;
use NaturaSiberica\Api\Repositories\User\AddressRepository;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Services\Mindbox\Sale\OrderService as MindboxOrderService;
use NaturaSiberica\Api\Services\Mindbox\User\ProfileService as MindboxProfileService;
use NaturaSiberica\Api\Tools\Settings\Options;
use NaturaSiberica\Api\Validators\ResultValidator;
use NaturaSiberica\Api\Validators\Sale\OrderValidator;
use NaturaSiberica\Api\Validators\Sale\PaymentValidator;
use NaturaSiberica\Api\Validators\User\AddressValidator;
use NaturaSiberica\Api\Validators\User\UserValidator;

Loader::includeModule('mlk.delivery');
Loc::loadMessages(__FILE__);
Loc::loadMessages(dirname(__DIR__, 2) . '/mindbox/sale/orderservice.php');
Loc::getMessage(dirname(__DIR__, 2) . '/repositories/sale/orderrepository.php');

class OrderService implements OrderServiceInterface
{
    private CartRepository                 $cartRepository;
    private OrderRepository                $orderRepository;
    private PaymentRepository              $paymentRepository;
    private DeliveryRepository             $deliveryRepository;
    private UserRepository                 $userRepository;
    private AddressRepository              $addressRepository;
    private StatusRepository               $statusRepository;
    private OrderValidator                 $orderValidator;
    private UserValidator                  $userValidator;
    private ResultValidator                $resultValidator;
    private MindboxOrderServiceInterface   $mbOrderService;
    private MindboxProfileServiceInterface $mbProfileService;

    private ?Order $order = null;
    private Logger $logger;

    private string $siteId;

    private array $customerProperties = [
        'name'     => 'NAME',
        'lastName' => 'SURNAME',
        'phone'    => 'PHONE',
        'email'    => 'EMAIL',
    ];

    private array $addressProperties = [
        'city'        => 'TOWN',
        'street'      => 'STREET',
        'houseNumber' => 'HOUSE',
        'flat'        => 'FLAT',
        'entrance'    => 'PODYEZD',
        'floor'       => 'ETAJ',
        'doorPhone'   => 'DOOR_CODE',
    ];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->cartRepository     = new CartRepository();
        $this->orderRepository    = new OrderRepository();
        $this->paymentRepository  = new PaymentRepository();
        $this->deliveryRepository = new DeliveryRepository();
        $this->userRepository     = new UserRepository();
        $this->addressRepository  = new AddressRepository();
        $this->statusRepository   = new StatusRepository();
        $this->orderValidator     = new OrderValidator();
        $this->userValidator      = new UserValidator();
        $this->resultValidator    = new ResultValidator();
        $this->mbOrderService     = new MindboxOrderService();
        $this->mbProfileService   = new MindboxProfileService($this->userRepository);
        $this->siteId             = Context::getCurrent()->getSite();
        $this->logger             = Logger::getInstance('order');
    }

    /**
     * @param int $fuserId
     *
     * @return void
     * @throws Exception
     */
    public function initBasket(int $fuserId)
    {
        $this->cartRepository->initBasket($fuserId);
    }

    /**
     * @param int      $fuserId
     * @param int      $userId
     * @param int|null $id
     * @param bool     $archive
     *
     * @return array
     * @throws Exception
     */
    public function show(int $fuserId, int $userId, int $id = null, bool $archive = false): array
    {
        UserValidator::validateFuserByUser($fuserId, $userId);

        $result = [];

        if ($id !== null) {
            $this->orderValidator->validateUserOrder($userId, $id);
            /**
             * @var OrderDTO $order
             */
            $order           = $this->orderRepository->findById($id);
            $result['order'] = $order->except('userId')->toArray();

            return $result;
        }

        $field = ($archive ? '' : '!') . 'STATUS_ID';
        $this->orderRepository->addFilter($field, Options::getArchivedOrderStatuses());

        $result['list'] = $this->orderRepository->findByUserId($userId);

        return $result;
    }

    /**
     * @param int $fuserId
     *
     * @return array
     * @throws Exception
     */
    public function checkCart(int $fuserId): array
    {
        UserValidator::validateFuser($fuserId);

        return [
            'enableOrder' => $this->cartRepository->check($fuserId),
        ];
    }

    /**
     * @param int   $fuserId
     * @param int   $userId
     * @param array $body
     *
     * @return array
     * @throws Exception
     */
    public function create(int $fuserId, int $userId, array $body): array
    {
        $this->orderValidator->validate($body);
        $this->orderValidator->validateAllowedPaySystemByDelivery($body['paySystemId'], $body['delivery']['id']);
        $this->orderValidator->validateProductItems();
        UserValidator::validateFuserByUser($fuserId, $userId);

        $this->order = Order::create($this->siteId, $userId);
        $this->cartRepository->initBasket($fuserId);

        $isEnabledSendDataInMindbox = Options::isEnabledSendDataInMindbox();

        if ($isEnabledSendDataInMindbox) {
            $this->mbOrderService->setCartRepository($this->cartRepository);
            $mbResponse = $this->mbOrderService->calculateAuthorizedCart($userId, $body, true)->getBody();
            $this->calculateCart($mbResponse, $body);

            if ($body['bonuses']['use']) {
                $this->addBonuses($body['bonuses']['amount']);
            }

            if($body['coupon']) {
                $this->addCoupons($body['coupon'], $mbResponse['order']['couponsInfo']);
            }

            if(isset($mbResponse['order']['deliveryCost'])) {
                $body['delivery']['price'] = $mbResponse['order']['deliveryCost'];
            }
        }

        $cart = $this->cartRepository->getBasket();

        OrderValidator::validateBasketOnNull($cart);
        OrderValidator::validateEmptyBasket($cart->getBasketItems());

        $cartItems = $this->cartRepository->getCartItems();
        $this->cartRepository->validateCartItemsInStore($cartItems);
        $this->order->setBasket($cart);

        $this->addDelivery($body['delivery']);
        $this->addPayment($body['paySystemId']);
        $this->addComment($body['comments']);

        if (! empty($body['addressId'])) {
            $addressDTO = $this->addressRepository->findById($body['addressId'])->get();

            AddressValidator::validateDTO($addressDTO);

            if ($body['delivery']['typeCode'] === DeliveryRepository::DELIVERY_TYPE_CODE_COURIER) {
                $body['delivery']['address'] = $addressDTO->fullAddress;
            }

            $address = $addressDTO->except('userId')->toArray();
            $this->addAddress($address);
        }
        $this->deliveryRepository->setCityId($body['cityId']);
        if ($body['delivery']['typeCode'] === DeliveryRepository::DELIVERY_TYPE_CODE_ORDER_PICKUP_POINT) {
            $pickupPointDto = $this->deliveryRepository->getDeliveryDataRepository()->findByCode($body['delivery']['code']);
            if ($pickupPointDto) {
                $body['delivery']['address'] = $pickupPointDto->name;
            }
        }

        $body['delivery']['name'] = $this->deliveryRepository->findById($this->order, $body['delivery']['id'], 'name');
        $body['delivery']['cityId'] = $body['cityId'];

        /**
         * @var PaySystemDTO $paySystemDto
         */
        $paySystemDto = $this->paymentRepository->findById($fuserId, $body['paySystemId'])->get();
        $payment      = [
            'type'   => $paySystemDto->code,
            'amount' => $this->order->getPrice(),
        ];

        $user = $this->userRepository->findById($userId)->get()->toArray();

        $this->addCityId($body['cityId']);

        $customerValidationData = [
            'email' => $body['customer']['email'],
        ];

        $this->userValidator->setRequestBody($customerValidationData);
        $this->userValidator->validateCorrectEmail();
        $this->userValidator->validateEmailLength();

        $this->addCustomer($user, $body['customer']);

        if ($isEnabledSendDataInMindbox) {
            $transaction = $this->mbOrderService->startOrderTransaction($userId, $payment, $body, $mbResponse)->getBody();
            if ($transaction['order']['processingStatus'] === MindboxOrderServiceInterface::RESPONSE_PROCESSING_STATUS_PRICE_CHANGED) {
                $this->mbOrderService->rollbackOrderTransaction();
                $message = '#3: '.Loc::getMessage('error_price_has_been_changed');
                $this->logger->error($message, $transaction);
                throw new Exception($message, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
            }
        }

        $this->order->doFinalAction(true);
        $result = $this->order->save();

        $this->resultValidator->validate($result);

        $orderId    = $result->getId();
        $response   = [
            'orderId' => $orderId,
        ];
        $paymentUrl = PaymentRepository::getPaymentUrl($this->order->getPaymentCollection());

        if (! empty($paymentUrl)) {
            $response['paymentUrl'] = $paymentUrl;
        }

        if ($isEnabledSendDataInMindbox) {
            $transaction['order']['ids']['websiteId'] = $orderId;
            $this->mbOrderService->commitOrderTransaction($transaction['order']['ids']);
            $this->mbOrderService->prepareTransactionId($orderId);
        }

        return $response;
    }

    /**
     * @param array $mbResponse
     * @param array $requestBody
     *
     * @return void
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function calculateCart(array $mbResponse, array $requestBody): void
    {
        if (empty($this->mbOrderService->getCartRepository())) {
            $this->mbOrderService->setCartRepository($this->cartRepository);
        }

        $processingStatus = $mbResponse['order']['processingStatus'];

        if ($processingStatus === $this->mbOrderService::RESPONSE_PROCESSING_STATUS_PRICE_CHANGED) {
            throw new Exception('#1: '.Loc::getMessage('error_price_has_been_changed'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        if ($requestBody['bonuses']['use']) {
            $bonusPointsInfo = $mbResponse['order']['bonusPointsInfoPerBalanceType'];
            $bonus = $requestBody['bonuses'];

            foreach ($bonusPointsInfo as $bonusPoint) {
                if ($bonus['systemName'] !== $bonusPoint['balanceType']['ids']['systemName']) {
                    continue;
                }

                if ((int)$bonusPoint['availableAmountForCurrentOrder'] < $bonus['amount']) {
                    throw new Exception(Loc::getMessage('error_available_bonuses'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
                }
            }
        }

        if($requestBody['coupon']) {
            $couponsInfo = current($mbResponse['order']['couponsInfo']);

            if ($couponsInfo['coupon']['ids']['code'] !== $requestBody['coupon']) {
                throw new Exception('Не верный промо код', StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
            }
        }

        $lines  = $mbResponse['order']['lines'];
        $basket = $this->cartRepository->getBasket();

        $cartItems = $this->cartRepository->get()->items;
        $productItems = $requestBody['productItems'];

        /**
         * @var CartItemDTO $cartItem
         */
        foreach ($cartItems as $cartItem) {
            $basketItem = $basket->getItemByXmlId($cartItem->xmlId);

            $productRow = current(
                array_filter($productItems, function ($productItem) use ($cartItem) {
                    return $cartItem->product['id'] === $productItem['id'];
                })
            );
            $lineRow    = current(
                array_filter($lines, function ($line) use ($productRow) {
                    return $productRow['article'] === $line['product']['ids']['naturaSiberica'];
                })
            );

            $mbFormattedBasePrice     = PriceHelper::format($lineRow['basePricePerItem']);
            $mbFormattedDiscountPrice = PriceHelper::format($lineRow['discountedPriceOfLine']);

            if ($mbFormattedBasePrice !== $productRow['basePrice'] || $mbFormattedDiscountPrice !== $productRow['discountPrice']) {
                throw new Exception('#2: '.Loc::getMessage('error_price_has_been_changed'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
            }

            $lineBasePrice     = $lineRow['basePricePerItem'];
            $lineQuantity      = (int)$lineRow['quantity'];
            $lineDiscountPrice = $lineQuantity > ModuleInterface::ONE ? ($lineRow['discountedPriceOfLine'] / $lineQuantity) : $lineRow['discountedPriceOfLine'];

            $discount = $lineBasePrice - $lineDiscountPrice;

            $basketItem->setField('PRICE', $lineDiscountPrice);
            $basketItem->setField('CUSTOM_PRICE', 'Y');
            $basketItem->setField('BASE_PRICE', $lineBasePrice);
            $basketItem->setField('DISCOUNT_PRICE', $discount);

            $linePromotions = [];
            $discountPromotions = array_filter($lineRow['appliedPromotions'], function($item) {
                return $item['type'] === 'discount';
            });
            foreach ($discountPromotions as $promotion) {
                if (!empty($promotion['coupon'])) {
                    $linePromotions[] = [
                        'NAME' => $promotion['promotion']['name'],
                        'VALUE' => 'action',
                        'CODE' => $promotion['coupon']['pool']['ids']['externalId']
                    ];
                } else {
                    $linePromotions[] = [
                        'NAME' => $promotion['promotion']['name'],
                        'VALUE' => 'action',
                        'CODE' => $promotion['promotion']['ids']['externalId']
                    ];
                }
            }
            $collection = $basketItem->getPropertyCollection();
            $collection->redefine($linePromotions);
        }

        $save = $basket->save();

        $this->resultValidator->validate($save);
        $this->cartRepository->setBasket($basket);
    }

    /**
     * @param array $delivery
     *
     * @return void
     * @throws Exception
     */
    private function addDelivery(array $delivery): void
    {
        $service  = DeliveryServiceManager::getObjectById($delivery['id']);
        $shipment = $this->order->getShipmentCollection()->createItem($service);
        $shipment->setFields([
            'PRICE_DELIVERY'        => $delivery['price'],
            'CUSTOM_PRICE_DELIVERY' => 'Y',
        ]);

        $property = $this->order->getPropertyCollection()->getItemByOrderPropertyCode(Constants::ORDER_PROPERTY_SELECTED_DELIVERY_CODE);
        $property->setValue($delivery['code']);
    }

    /**
     * @param int $paySystemId
     *
     * @return void
     * @throws Exception
     */
    private function addPayment(int $paySystemId): void
    {
        $service = PaymentServiceManager::getObjectById($paySystemId);

        $payment = $this->order->getPaymentCollection()->createItem($service);
        $payment->setField('SUM', $this->order->getPrice());

        $this->order->setPersonTypeId($this->getPhysicalPersonTypeId());
    }

    private function getPhysicalPersonTypeId(): int
    {
        $personType = $this->getPhysicalPersonType();
        return $personType !== null ? $personType->getId() : $this->createPhysicalPersonType();
    }

    /**
     * @return EO_PersonType|null
     * @throws Exception
     */
    private function getPhysicalPersonType(): ?EO_PersonType
    {
        return PersonTypeTable::getList([
            'filter' => ['=NAME' => Loc::getMessage('person_type_name_physical')],
            'select' => ['ID'],
        ])->fetchObject();
    }

    /**
     * @return int
     * @throws Exception
     */
    private function createPhysicalPersonType(): int
    {
        /**
         * @var Result $addResult
         */
        $addResult = PersonTypeTable::add([
            'NAME'                 => Loc::getMessage('person_type_name_physical'),
            'ACTIVE'               => true,
            'LID'                  => $this->siteId,
            'CODE'                 => 'person',
            'XML_ID'               => 'person',
            'ENTITY_REGISTRY_TYPE' => 'ORDER',
        ]);

        return $addResult->isSuccess() ? $addResult->getId() : 0;
    }

    /**
     * @param int $bonuses
     *
     * @return void
     * @throws Exception
     */
    private function addBonuses(int $bonuses)
    {
        $property = $this->order->getPropertyCollection()->getItemByOrderPropertyCode('MINDBOX_BONUS');
        $property->setField('VALUE', $bonuses);
    }

    private function addCoupons(string $coupon, array $couponInfo)
    {
        $property = $this->order->getPropertyCollection()->getItemByOrderPropertyCode('MINDBOX_PROMO_CODE');
        $property->setField('VALUE', $coupon);

        $couponValue = current($couponInfo);
        if($couponValue) {
            $property = $this->order->getPropertyCollection()->getItemByOrderPropertyCode('MINDBOX_PROMO_VALUE');
            $property->setField('VALUE', $couponValue['discountAmountForCurrentOrder']);
        }

    }

    public function addComment(string $comment = null)
    {
        $this->order->setField('USER_DESCRIPTION', $comment);
    }

    /**
     * @param array $address
     *
     * @return void
     * @throws Exception
     */
    private function addAddress(array $address): void
    {
        foreach ($this->addressProperties as $bodyProp => $orderProp) {
            $property = $this->order->getPropertyCollection()->getItemByOrderPropertyCode($orderProp);
            $property->setField('VALUE', $address[$bodyProp]);
        }
    }

    /**
     * @param int $cityId
     *
     * @return void
     * @throws Exception
     */
    private function addCityId(int $cityId)
    {
        $property = $this->order->getPropertyCollection()->getItemByOrderPropertyCode('CITY_ID');
        $property->setValue($cityId);
    }

    /**
     * @param array      $user
     * @param array|null $customer
     *
     * @return void
     * @throws Exception
     */
    private function addCustomer(array $user = [], array $customer = null): void
    {
        foreach ($this->customerProperties as $bodyProperty => $orderProperty) {
            $property = $this->order->getPropertyCollection()->getItemByOrderPropertyCode($orderProperty);
            $value    = $customer[$bodyProperty] ?? $user[$bodyProperty];
            $property->setField('VALUE', $value);
        }
    }

    /**
     * @param int $fuserId
     * @param int $userId
     * @param int $id
     *
     * @return array
     *
     * @throws Exception
     */
    public function cancel(int $fuserId, int $userId, int $id): array
    {
        UserValidator::validateFuserByUser($fuserId, $userId);
        $this->orderValidator->validateUserOrder($userId, $id);

        $cancel = $this->orderRepository->cancel($id);

        return [
            'canceled' => $cancel,
            'message'  => Loc::getMessage('SUCCESSFUL_CANCELED_ORDER'),
        ];
    }

    /**
     * @param array $filter
     *
     * @return array
     *
     * @throws Exception
     */
    public function getStatuses(array $filter): array
    {
        return [
            'list' => $this->statusRepository->findBy($filter)->all(),
        ];
    }

    //region ТИП ПЛАТЕЛЬЩИКА

    /**
     * @param int $fuserId
     * @param int $userId
     * @param int $deliveryId
     *
     * @return array
     * @throws Exception
     */
    public function getPayments(int $fuserId, int $userId, int $deliveryId): array
    {
        UserValidator::validateFuserByUser($fuserId, $userId);

        return [
            'list' => $this->paymentRepository->getPayments($fuserId, $deliveryId),
        ];
    }

    /**
     * @param int $orderId
     * @param int $fuserId
     * @param int $userId
     *
     * @return array
     * @throws Exception
     */
    public function getPaymentUrl(int $orderId, int $fuserId, int $userId): array
    {
        $order             = Order::load($orderId);
        $paymentCollection = $order->getPaymentCollection();

        /**
         * @var Payment $payment
         */
        $payment = $paymentCollection[0];
        $service = Manager::getObjectById($payment->getPaymentSystemId());

        PaymentValidator::validatePaySystemIsCash($service);

        UserValidator::validateFuserByUser($fuserId, $userId);
        OrderValidator::validateCancelledOrder($order);
        OrderValidator::validateOrderById($orderId, $order);
        OrderValidator::validateOrderByUser($order, $userId);

        return [
            'paymentUrl' => PaymentRepository::getPaymentUrl($order->getPaymentCollection()),
        ];
    }

    /**
     * @param int      $fuserId
     * @param int|null $userId
     * @param int      $cityId
     *
     * @return array
     * @throws Exception
     */
    public function getDeliveries(int $fuserId, ?int $userId, int $cityId): array
    {
        if ($userId) {
            UserValidator::validateFuserByUser($fuserId, $userId);
        }

        return [
            'list' => $this->deliveryRepository->getDeliveries($fuserId, $userId, $cityId),
        ];
    }

    /**
     * @param int      $fuserId
     * @param int|null $userId
     *
     * @return array
     * @throws Exception
     */
    public function getFreeShipping(int $fuserId, ?int $userId): array
    {
        if ($userId) {
            UserValidator::validateFuserByUser($fuserId, $userId);
        }

        return [
            'freeShippingFrom' => Options::getFreeShippingFrom(),
        ];
    }
}
