<?php

namespace NaturaSiberica\Api\Validators\Sale;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Delivery\Services\Table;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;
use NaturaSiberica\Api\Validators\Validator;

Loc::loadMessages(__DIR__ . '/ordervalidator.php');
Loc::loadMessages(__FILE__);

class DeliveryValidator extends Validator
{
    use HighloadBlockTrait;

    public array $requiredFields = [
        'id',
        'typeCode',
        'code',
        'price',
    ];

    private array $delivery     = [];
    private array $deliveryData = [];

    /**
     * @param array $deliveryBody
     *
     * @return void
     * @throws Exception
     */
    public function validate(array $deliveryBody): void
    {
        $this->setRequestBody($deliveryBody);
        $this->validateEmptyRequestBody();
        $this->validateRequiredFields();
        $this->throwErrors();

        $delivery     = $this->getDelivery($deliveryBody['id']);
        $deliveryData = $this->getDeliveryDataElement(['UF_NOM_CODE' => $deliveryBody['code']]);

        $this->setDelivery($delivery ? : []);
        $this->setDeliveryData($deliveryData ? : []);

        $this->validateDeliveryId();
        $this->validateEmptyDeliveryData();
        $this->validateTypeCode();
        $this->validateDeliveryPrice();
    }

    /**
     * @param array $delivery
     */
    private function setDelivery(array $delivery): void
    {
        $this->delivery = $delivery;
    }

    /**
     * @param array $deliveryData
     */
    private function setDeliveryData(array $deliveryData): void
    {
        $this->deliveryData = $deliveryData;
    }

    /**
     * @return DataManager|string
     */
    private function getMlkDeliveryDataEntity()
    {
        return $this->getHlEntityByEntityName(ConstantEntityInterface::HLBLOCK_MLK_DELIVERY_DATA)->getDataClass();
    }

    /**
     * @param $id
     *
     * @return array|false
     */
    private function getDelivery($id)
    {
        return Table::getById($id)->fetch();
    }

    /**
     * @param array $filter
     *
     * @return array|false
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function getDeliveryDataElement(array $filter)
    {
        return $this->getMlkDeliveryDataEntity()::getList([
            'filter' => $filter,
        ])->fetch();
    }

    /**
     * @return array
     */
    public function getPropertyTitles(): array
    {
        return [
            'id'       => Loc::getMessage('delivery_id'),
            'code'     => Loc::getMessage('delivery_code'),
            'typeCode' => Loc::getMessage('delivery_type_code'),
            'price'    => Loc::getMessage('delivery_price'),
        ];
    }

    /**
     * @return void
     * @throws Exception
     */
    public function validateEmptyRequestBody(): void
    {
        if (empty($this->requestBody)) {
            throw new Exception(Loc::getMessage('error_empty_delivery'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function validateDeliveryId(): void
    {
        if (empty($this->delivery)) {
            throw new Exception(Loc::getMessage('error_incorrect_delivery_id'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function validateEmptyDeliveryData(): void
    {
        if (empty($this->deliveryData)) {
            throw new Exception(
                Loc::getMessage('error_empty_delivery_data'), StatusCodeInterface::STATUS_BAD_REQUEST
            );
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function validateTypeCode(): void
    {
        if ($this->delivery['CODE'] !== $this->deliveryData['UF_DELIVERY_TYPECODE'] || $this->delivery['CODE'] !== $this->requestBody['typeCode'] || $this->deliveryData['UF_DELIVERY_TYPECODE'] !== $this->requestBody['typeCode']) {
            throw new Exception(Loc::getMessage('error_incorrect_delivery_code'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function validateDeliveryPrice(): void
    {
        if ($this->requestBody['price'] !== (int)$this->deliveryData['UF_PRICE']) {
            throw new Exception(Loc::getMessage('error_incorrect_delivery_price'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }
}
