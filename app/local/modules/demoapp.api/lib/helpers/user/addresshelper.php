<?php

namespace NaturaSiberica\Api\Helpers\User;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PropertyValueCollection;
use Bitrix\Sale\PropertyValueCollectionBase;
use Exception;
use NaturaSiberica\Api\DTO\Sale\DeliveryDataDTO;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface as Constants;
use NaturaSiberica\Api\Repositories\Sale\DeliveryDataRepository;

Loc::loadMessages(__FILE__);

class AddressHelper
{
    protected static array $orderProperties = [
        'city'        => Constants::ORDER_PROPERTY_CITY,
        'street'      => Constants::ORDER_PROPERTY_STREET,
        'houseNumber' => Constants::ORDER_PROPERTY_HOUSE_NUMBER,
        'flat'        => Constants::ORDER_PROPERTY_FLAT,
        'entrance'    => Constants::ORDER_PROPERTY_ENTRANCE,
        'floor'       => Constants::ORDER_PROPERTY_FLOOR,
        'doorPhone'   => Constants::ORDER_PROPERTY_DOOR_PHONE,
    ];

    protected static function getPropertiesShortLabels(): array
    {
        return [
            'city'        => Loc::getMessage('property_short_label_city'),
            'street'      => Loc::getMessage('property_short_label_street'),
            'houseNumber' => Loc::getMessage('property_short_label_houseNumber'),
            'flat'        => Loc::getMessage('property_short_label_flat'),
            'entrance'    => Loc::getMessage('property_short_label_entrance'),
            'floor'       => Loc::getMessage('property_short_label_floor'),
            'doorPhone'   => Loc::getMessage('property_short_label_doorPhone'),
        ];
    }

    protected static function getPropertyShortLabel(string $code): string
    {
        $labels = self::getPropertiesShortLabels();
        return $labels[$code];
    }

    /**
     * @param PropertyValueCollection $propertyCollection
     * @param string                  $typeCode
     *
     * @return string|void
     *
     * @throws Exception
     */
    public static function getAddress(PropertyValueCollectionBase $propertyCollection, string $typeCode)
    {
        switch ($typeCode) {
            case 'CUR':
                $address = [];

                foreach (self::$orderProperties as $addressProperty => $orderProperty) {
                    $property = $propertyCollection->getItemByOrderPropertyCode($orderProperty);
                    if (!empty($property->getValue())) {
                        $address[] = sprintf('%s %s', self::getPropertyShortLabel($addressProperty), $property->getValue());
                    }
                }

                return !empty($address) ? implode(', ', $address) : null;

            case 'PVZ':
                $deliveryDataRepository = new DeliveryDataRepository();
                $propertyDeliveryCode = $propertyCollection->getItemByOrderPropertyCode(Constants::ORDER_PROPERTY_SELECTED_DELIVERY_CODE);
                $code = $propertyDeliveryCode->getValue();

                if (empty($code)) {
                    return;
                }

                $filter = [
                    '=typeCode' => $typeCode,
                    '=code' => $code
                ];

                $deliveryData = $deliveryDataRepository->find($filter);

                if (!empty($deliveryData)) {
                    $dto = new DeliveryDataDTO($deliveryData[0]);
                    return sprintf('%s %s, %s', self::getPropertyShortLabel('city'), $dto->city, $dto->name);
                }
        }
    }


}
