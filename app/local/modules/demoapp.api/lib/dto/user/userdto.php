<?php

namespace NaturaSiberica\Api\DTO\User;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\Date;
use Bitrix\Sale\Fuser;
use NaturaSiberica\Api\DTO\AbstractDTO;
use NaturaSiberica\Api\DTO\DTO;
use NaturaSiberica\Api\DTO\Sale\StoreDTO;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Interfaces\Services\TokenServiceInterface;
use NaturaSiberica\Api\Traits\NormalizerTrait;
use ReflectionException;
use Spatie\DataTransferObject\DataTransferObject;

class UserDTO extends DataTransferObject implements DTOInterface
{
    use NormalizerTrait;

    public ?int $id      = null;
    public ?int $fuserId = null;

    public string $phone;
    public string $login;

    public ?string $name                 = null;
    public ?string $secondName           = null;
    public ?string $lastName             = null;
    public ?string $loyaltyCard          = null;
    public ?string $bonuses              = null;
    public ?string $personalDiscount     = null;
    public ?string $email                = null;
    public ?int    $cityId               = null;
    public ?string $city                 = null;
    public ?string $gender               = null;
    public ?string $status               = null;
    public ?int    $maritalStatus        = null;
    public ?string $maritalStatusValue   = null;
    public ?int    $favoriteStore        = null;
    public ?string $favoriteStoreAddress = null;
    public ?int    $skinType             = null;

    public ?string $uid       = null;
    public ?string $birthdate = null;

    public ?int $mindboxId             = null;
    public bool $mindboxPhoneConfirmed = false;
    public bool $emailConfirmed = false;

    public ?bool $subscribedToEmail = null;
    public ?bool $subscribedToPush  = null;
    public ?bool $subscribedToSms   = null;

    protected function excludedFields(): array
    {
        return ['id', 'fuserId', 'login'];
    }

    protected function readonlyFields(): array
    {
        return [
            'id',
            'phone',
            'login',
            'status',
            'loyaltyCard',
            'bonuses',
            'personalDiscount',
        ];
    }

    protected function requiredParameters(): array
    {
        return [];
    }

    public static function convertFromBitrixFormat(array $fields)
    {
        return new static([
            'id'                    => ! empty($fields['ID']) ? (int)$fields['ID'] : null,
            'fuserId'               => ! empty($fields['FUSER_ID']) ? (int)$fields['FUSER_ID'] : null,
            'login'                 => $fields['LOGIN'],
            'phone'                 => $fields['PHONE'],
            'name'                  => $fields['NAME'] ? : null,
            'secondName'            => $fields['SECOND_NAME'] ? : null,
            'lastName'              => $fields['LAST_NAME'] ? : null,
            'cityId'                => ! empty($fields['CITY_ID']) ? (int)$fields['CITY_ID'] : null,
            'city'                  => $fields['CITY'] ? : null,
            'loyaltyCard'           => $fields['LOYALTY_CARD'] ? : null,
            'bonuses'               => $fields['BONUSES'] ? : null,
            'personalDiscount'      => $fields['PERSONAL_DISCOUNT'] ? : null,
            'email'                 => $fields['EMAIL'] ? : null,
            'gender'                => $fields['GENDER'] ? : null,
            'status'                => $fields['STATUS'] ? : null,
            'maritalStatus'         => ! empty($fields['MARITAL_STATUS']) ? (int)$fields['MARITAL_STATUS'] : null,
            'maritalStatusValue'    => $fields['MARITAL_STATUS_VALUE'] ? : null,
            'favoriteStore'         => ! empty($fields['FAVORITE_STORE']) ? (int)$fields['FAVORITE_STORE'] : null,
            'favoriteStoreAddress'  => $fields['FAVORITE_STORE_ADDRESS'] ? : null,
            'skinType'              => ! empty($fields['SKIN_TYPE']) ? (int)$fields['SKIN_TYPE'] : null,
            'birthdate'             => $fields['BIRTHDATE'] instanceof Date ? $fields['BIRTHDATE']->format(
                TokenServiceInterface::DEFAULT_DATE_FORMAT
            ) : null,
            'subscribedToEmail'     => (bool)$fields['SUBSCRIBED_TO_EMAIL'],
            'subscribedToSms'       => (bool)$fields['SUBSCRIBED_TO_SMS'],
            'subscribedToPush'      => (bool)$fields['SUBSCRIBED_TO_PUSH'],
            'uid'                   => $fields['UID'] ? : null,
            'mindboxId'             => (int)$fields['MINDBOX_ID'] ? : null,
            'mindboxPhoneConfirmed' => (bool)$fields['MINDBOX_PHONE_CONFIRMED'],
            'emailConfirmed' => (bool)$fields['EMAIL_CONFIRMED'],
        ]);
    }

    public function convertToBitrixFormat(bool $excludeId = false)
    {
        if ($excludeId) {
            $this->except('id', 'fuserId', 'login');
        }

        $result = [];

        foreach ($this->toArray() as $key => $value) {
            $field          = $this->convertCamelToSnake($key, true);
            $result[$field] = $value;
        }

        return $result;
    }

    public function modify(array $fields): DTOInterface
    {
        $rc = new \ReflectionClass($this);
        foreach ($fields as $key => $value) {
            if (in_array($key, $this->readonlyFields())) {
                continue;
            }

            if ($rc->hasProperty($key)) {
                $property = $rc->getProperty($key);
                $type     = $property->getType()->getName();

                if ($type === 'int' && gettype($value) === 'string') {
                    $value = (int)$value;
                }

                $rc->getProperty($key)->setValue($this, $value);
            }
        }

        return $this;
    }

    public static function createFromPhone(string $phone): UserDTO
    {
        return new static([
            'phone' => $phone,
            'login' => $phone
        ]);
    }
}
