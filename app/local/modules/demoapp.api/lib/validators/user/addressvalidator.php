<?php

namespace NaturaSiberica\Api\Validators\User;

use Bitrix\Main\Localization\Loc;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\Error\ErrorDTO;
use NaturaSiberica\Api\DTO\User\AddressDTO;
use NaturaSiberica\Api\Validators\Validator;

Loc::loadMessages(__FILE__);

class AddressValidator extends Validator
{
    public array $requiredFields = [
        'fiasId',
        'region',
        'city',
        'street',
        'houseNumber',
        'latitude',
        'longitude',
    ];

    public array $booleanFields = ['default', 'privateHouse'];

    public function getPropertyTitles(): array
    {
        return [
            'fiasId' => Loc::getMessage('PROPERTY_FIAS_ID'),
            'region' => Loc::getMessage('PROPERTY_REGION'),
            'city' => Loc::getMessage('PROPERTY_CITY'),
            'street' => Loc::getMessage('PROPERTY_STREET'),
            'houseNumber' => Loc::getMessage('PROPERTY_HOUSE_NUMBER'),
            'flat' => Loc::getMessage('PROPERTY_FLAT'),
            'floor' => Loc::getMessage('PROPERTY_FLOOR'),
            'entrance' => Loc::getMessage('PROPERTY_ENTRANCE'),
            'doorPhone' => Loc::getMessage('PROPERTY_DOOR_PHONE'),
            'latitude' => Loc::getMessage('PROPERTU_LATITUDE'),
            'longitude' => Loc::getMessage('PROPERTY_LONGITUDE'),
        ];
    }

    public function getPropertyTitle(string $parameter): string
    {
        $propertyTitles = $this->getPropertyTitles();
        return $propertyTitles[$parameter];
    }

    /**
     * @return void
     * @throws Exception
     */
    public function validateRequiredFields()
    {
        foreach ($this->requiredFields as $requiredField) {
            if (! array_key_exists($requiredField, $this->requestBody) || empty($this->requestBody[$requiredField])) {
                $type = sprintf('empty_%s', $requiredField);
                $message = Loc::getMessage('error_empty_required_field', [
                    '#field#' => $this->getPropertyTitle($requiredField)
                ]);
                $errorDto = ErrorDTO::createFromParameters($type, $this->errorCode, $message);
                $this->addCustomError($errorDto);
            }
        }

        $this->throwErrors();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function validateOnEmptyValues()
    {
        foreach ($this->requestBody as $field => $value) {
            if (! in_array($field, $this->booleanFields) && in_array($field, $this->requiredFields) && strlen(
                    preg_replace('/\s+/u', '', $value)
                ) === 0) {
                $type = sprintf('empty_%s', $field);
                $message = Loc::getMessage('error_empty_field', ['#field#' => $field]);
                $errorDto = ErrorDTO::createFromParameters($type, $this->errorCode, $message);

                $this->addCustomError($errorDto);
            }
        }

        $this->throwErrors();
    }

    /**
     * @param AddressDTO|false|null $dto
     * @param int                   $code
     *
     * @return bool
     * @throws Exception
     */
    public static function validateDTO($dto, int $code = StatusCodeInterface::STATUS_BAD_REQUEST): bool
    {
        if ($dto instanceof AddressDTO) {
            return true;
        }

        throw new Exception(Loc::getMessage('error_address_not_found'), $code);
    }
}
