<?php

namespace NaturaSiberica\Api\Repositories\Sale;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Context;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Delivery\Services\Table;
use Bitrix\Sale\Order;
use Bitrix\Sale\Services\Base\RestrictionManager;
use Bitrix\Sale\Shipment;
use Exception;
use NaturaSiberica\Api\DTO\Sale\DeliveryDataDTO;
use NaturaSiberica\Api\DTO\Sale\DeliveryDTO;
use NaturaSiberica\Api\DTO\Sale\DeliverySystemDTO;
use NaturaSiberica\Api\Exceptions\RepositoryException;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Factories\Sale\DeliveryFactory;
use NaturaSiberica\Api\Factories\Sale\DeliverySystemFactory;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Interfaces\Repositories\Sale\DeliveryRepositoryInterface;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;
use NaturaSiberica\Api\Validators\User\UserValidator;
use ReflectionException;

class DeliveryRepository implements DeliveryRepositoryInterface, ConstantEntityInterface, ModuleInterface
{
    use FileTrait, HighloadBlockTrait;

    private CartRepository         $cartRepository;
    private DeliveryDataRepository $deliveryDataRepository;

    private string $siteId;

    private ?int $cityId = null;

    /**
     * @var DeliverySystemDTO[]
     */
    private array $collection = [];

    public function __construct()
    {
        $this->cartRepository         = new CartRepository();
        $this->deliveryDataRepository = new DeliveryDataRepository();
        $this->siteId                 = Context::getCurrent()->getSite();
    }

    /**
     * @param int|null $cityId
     *
     * @return DeliveryRepository
     */
    public function setCityId(?int $cityId = null): DeliveryRepository
    {
        $this->cityId = $cityId;
        return $this;
    }

    /**
     * @return DeliveryDataRepository
     */
    public function getDeliveryDataRepository(): DeliveryDataRepository
    {
        return $this->deliveryDataRepository;
    }

    /**
     * Возвращает способы доставки
     *
     * @param int      $fuserId покупатель
     * @param int|null $userId  пользователь
     * @param int|null $cityId  город
     *
     * @return array
     *
     * @throws Exception
     */
    public function getDeliveries(int $fuserId, ?int $userId, int $cityId = null): array
    {
        if ($userId) {
            UserValidator::validateFuserByUser($fuserId, $userId);
        }
        $this->cartRepository->initBasket($fuserId);

        $order = Order::create($this->siteId, $userId);
        $order->setBasket($this->cartRepository->getBasket());

        $this->setCityId($cityId);
        $this->prepareCollection($order);

        return $this->collection;
    }

    /**
     * @param Order       $order
     * @param int         $deliveryId
     * @param string|null $returnField
     *
     * @return DeliverySystemDTO|string|false
     *
     * @throws ArgumentException
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws SystemException
     */
    public function findById(Order $order, int $deliveryId, string $returnField = null)
    {
        $this->prepareCollection($order);

        foreach ($this->collection as $dto) {
            if ($dto->id === $deliveryId) {
                return $returnField !== null ? $dto->$returnField : $dto;
            }
        }

        return false;
    }

    /**
     * @param Order $order
     *
     * @return DeliveryRepository
     *
     * @throws ArgumentException
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws SystemException
     */
    private function prepareCollection(Order $order): DeliveryRepository
    {
        $this->deliveryDataRepository->setCityId($this->cityId);

        $shipmentCollection = $order->getShipmentCollection();
        $shipment           = Shipment::createSystem($shipmentCollection);
        $services           = Manager::getRestrictedList($shipment, RestrictionManager::MODE_CLIENT);
        $collection         = $this->deliveryDataRepository->getCollection();

        foreach ($services as $serviceId => $service) {
            $manager = Manager::getObjectById($serviceId);
            $code    = $manager->getCode();

            if ($manager->getParentId() === 0 || is_numeric($code) || empty($code)) {
                continue;
            }

            $attrs = [
                'id'       => (int)$manager->getId(),
                'name'     => $manager->getName(),
                'logo'     => $manager->getLogotipPath() ? : null,
                'typeCode' => $code,
            ];

            if (array_key_exists($code, $collection)) {
                $attrs['variants'] = $collection[$code];
            }

            $this->collection[] = new DeliverySystemDTO($attrs);
        }

        return $this;
    }

    /**
     * @param Order $order
     *
     * @return DeliverySystemDTO[]
     * @throws Exception
     */
    public function getCollection(Order $order): array
    {
        return $this->prepareCollection($order)->collection;
    }
}
