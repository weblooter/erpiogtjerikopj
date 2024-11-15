<?php

namespace NaturaSiberica\Api\Repositories\Sale;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Internals\EO_PersonType;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\Result;
use Exception;
use NaturaSiberica\Api\DTO\Sale\OrderDTO;
use NaturaSiberica\Api\Factories\Sale\OrderFactory;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Repositories\User\AddressRepository;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Validators\ResultValidator;
use NaturaSiberica\Api\Validators\Sale\OrderValidator;

Loc::loadMessages(__FILE__);

class OrderRepository
{
    use FileTrait;

    private CartRepository     $cartRepository;
    private UserRepository     $userRepository;
    private AddressRepository  $addressRepository;
    private DeliveryRepository $deliveryRepository;

    private ?Order $order = null;

    private OrderValidator  $orderValidator;
    private ResultValidator $resultValidator;

    private string $siteId;

    private array $recipientProperties = [
        'name'     => 'NAME',
        'lastName' => 'SURNAME',
        'phone'    => 'PHONE',
    ];

    private array $filter = [];

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
     * @var OrderDTO[]
     */
    private array $collection = [];

    public function __construct()
    {
        $this->cartRepository     = new CartRepository();
        $this->userRepository     = new UserRepository();
        $this->addressRepository  = new AddressRepository();
        $this->deliveryRepository = new DeliveryRepository();
        $this->orderValidator     = new OrderValidator();
        $this->resultValidator    = new ResultValidator();
        $this->siteId             = Context::getCurrent()->getSite();
    }

    /**
     * @return CartRepository
     */
    public function getCartRepository(): CartRepository
    {
        return $this->cartRepository;
    }

    /**
     * @return DeliveryRepository
     */
    public function getDeliveryRepository(): DeliveryRepository
    {
        return $this->deliveryRepository;
    }

    /**
     * @return Order|null
     */
    public function getOrder(): ?Order
    {
        return $this->order;
    }

    /**
     * @param Order|null $order
     */
    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function addFilter($field, $value)
    {
        $this->filter[$field] = $value;
    }

    /**
     * Получение заказа по ID пользователя
     *
     * @param int $userId ID пользователя
     *
     * @return DTOInterface[]|null
     *
     * @throws Exception
     */
    public function findByUserId(int $userId): ?array
    {
        $this->addFilter('USER_ID', $userId);
        $orders = Order::loadByFilter([
            'filter' => $this->filter,
            'order'  => ['DATE_INSERT' => 'DESC'],
        ]);
        /**
         * @var Order $order
         */
        foreach ($orders as $order) {
            $this->setOrder($order);
            $this->collection[] = OrderFactory::createDTO($this)->except('userId')->toArray();
        }

        return $this->collection;
    }

    /**
     * Получение заказа по ID
     *
     * @param int $id ID заказа
     *
     * @return OrderDTO|DTOInterface
     * @throws Exception
     */
    public function findById(int $id): OrderDto
    {
        $order = Order::load($id);

        OrderValidator::validateOrderById($id, $order);

        $this->setOrder($order);

        return OrderFactory::createDTO($this);
    }

    /**
     * Отмена заказа
     *
     * @param int $id ID заказа
     *
     * @return bool
     * @throws Exception
     */
    public function cancel(int $id): bool
    {
        $order = Order::load($id);
        OrderValidator::validateOrderById($id, $order);
        OrderValidator::validateCancelledOrder($order);
        $cancel = $order->setField('CANCELED', 'Y');

        $this->resultValidator->validate($cancel);

        $result = $order->save();

        $this->resultValidator->validate($result);

        return $result->isSuccess();
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
     *
     * @throws Exception
     */
    private function getPhysicalPersonTypeId(): int
    {
        $personType = $this->getPhysicalPersonType();
        return $personType !== null ? $personType->getId() : $this->createPhysicalPersonType();
    }

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
}
