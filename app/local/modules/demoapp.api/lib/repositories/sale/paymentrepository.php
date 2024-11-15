<?php

namespace NaturaSiberica\Api\Repositories\Sale;

use Bitrix\Main\Context;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Internals\DeliveryPaySystemTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaymentCollection;
use Bitrix\Sale\PaySystem\Manager;
use NaturaSiberica\Api\DTO\Sale\PaySystemDTO;
use NaturaSiberica\Api\Interfaces\Repositories\Sale\PaymentsRepositoryInterface;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use Spatie\DataTransferObject\DataTransferObject;

class PaymentRepository implements PaymentsRepositoryInterface
{
    use FileTrait;

    /**
     * @var PaySystemDTO[]
     */
    private array $collection = [];

    public function getPayments(int $fuserId, int $deliveryId): array
    {
        DiscountCouponsManager::init();

        $paymentIds        = DeliveryPaySystemTable::getLinks($deliveryId, DeliveryPaySystemTable::ENTITY_TYPE_DELIVERY);
        $logoIds           = [];
        $paymentCollection = $this->preparePaymentCollection($fuserId, $paymentIds, $logoIds);

        $this->prepareCollection($paymentCollection, $logoIds);

        return $this->collection;
    }

    public function findById(int $fuserId,int $paySystemId): PaymentsRepositoryInterface
    {
        $logoIds = [];
        $paymentCollection = $this->preparePaymentCollection($fuserId, [$paySystemId], $logoIds);

        $this->prepareCollection($paymentCollection, $logoIds);
        return $this;
    }

    public function all(): array
    {
        return $this->collection;
    }

    /**
     * @return PaySystemDTO|null
     */
    public function get(): ?DataTransferObject
    {
        return $this->collection[0];
    }

    public function getPaySystemsCodes(): array
    {
        $result = [];
        $list   = Manager::getList(['select' => ['ID', 'CODE']])->fetchAll();
        foreach ($list as $item) {
            $result[$item['ID']] = $item['CODE'];
        }

        return $result;
    }

    private function preparePaymentCollection(int $fuserId, array $paymentIds, array &$logoIds): PaymentCollection
    {
        $siteId            = Context::getCurrent()->getSite();
        $userId            = Fuser::getUserIdById($fuserId);
        $order             = Order::create($siteId, $userId);
        $paymentCollection = $order->getPaymentCollection();

        foreach ($paymentIds as $paymentId) {
            $paymentService = Manager::getObjectById($paymentId);
            $payment        = Payment::create($paymentCollection);

            $paySystemId     = $paymentService->getField('PAY_SYSTEM_ID');
            $paySystemName   = $paymentService->getField('NAME');
            $paySystemLogoId = $paymentService->getField('LOGOTIP');

            $payment->setFields([
                'PAY_SYSTEM_ID'   => $paySystemId,
                'PAY_SYSTEM_NAME' => $paySystemName,
            ]);

            $logoIds[$paySystemId] = $paySystemLogoId;
            $paymentCollection->addItem($payment);
        }

        return $paymentCollection;
    }

    private function prepareCollection(PaymentCollection $paymentCollection, array $logoIds): PaymentRepository
    {
        $logos = $this->getImagePath(array_values($logoIds));
        $codes = $this->getPaySystemsCodes();

        /**
         * @var Payment $item
         */
        foreach ($paymentCollection as $item) {
            $logoId             = $logoIds[$item->getPaymentSystemId()];
            $psId               = $item->getPaymentSystemId();
            $this->collection[] = new PaySystemDTO([
                'id'   => $psId,
                'name' => $item->getPaymentSystemName(),
                'code' => $codes[$psId],
                'logo' => $logos[$logoId],
            ]);
        }

        return $this;
    }

    /**
     * @param PaymentCollection $paymentCollection
     *
     * @return string
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getPaymentUrl(PaymentCollection $paymentCollection): string
    {
        /**
         * @var Payment $payment
         */
        foreach ($paymentCollection as $payment) {
            if ($payment === null) {
                continue;
            }

            return $payment->getPaySystem()->initiatePay($payment)->getPaymentUrl();
        }

        return '';
    }
}
