<?php

namespace NaturaSiberica\Api\Services\User;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;
use NaturaSiberica\Api\DTO\User\AddressDTO;
use NaturaSiberica\Api\Entities\UserAddressTable;
use NaturaSiberica\Api\Exceptions\AddressException;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Factories\AddressDTOFactory;
use NaturaSiberica\Api\Interfaces\Services\User\AddressServiceInterface;
use NaturaSiberica\Api\Interfaces\Services\User\ProfileServiceInterface;
use NaturaSiberica\Api\Repositories\User\AddressRepository;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Validators\User\AddressValidator;
use NaturaSiberica\Api\Validators\User\UserValidator;

Loc::loadMessages(__FILE__);

class ProfileService implements ProfileServiceInterface, AddressServiceInterface
{
    private UserRepository    $userRepository;
    private UserValidator     $userValidator;
    private AddressValidator  $addressValidator;
    private AddressRepository $addressRepository;

    public static array $excludedUserFields = [
        'id',
        'fuserId',
        'login',
        'city',
        'mindboxId',
        'mindboxPhoneConfirmed',
        'maritalStatusValue',
        'favoriteStoreAddress',
    ];

    public function __construct()
    {
        $this->userRepository    = new UserRepository();
        $this->userValidator     = new UserValidator($this->userRepository);
        $this->addressRepository = new AddressRepository();
        $this->addressValidator  = new AddressValidator();
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public function getProfile(int $userId): array
    {
        $userDTO = $this->userRepository->findById($userId)->get();

        return [
            'user' => $userDTO->except(...self::$excludedUserFields)->toArray(),
        ];
    }

    /**
     * @param int        $userId
     * @param array|null $body
     *
     * @return array
     *
     * @throws Exception
     */
    public function editProfile(int $userId, array $body): array
    {
        $result  = [];
        $userDTO = $this->userRepository->findById($userId)->get();

        $this->userValidator->validate($userDTO->toArray(), $body);

        $this->prepareBody($body);

        if (! empty($userDTO->birthdate) && array_key_exists('birthdate', $body)) {
            unset($body['birthdate']);
        }

        if (empty($body)) {
            return [
                'update' => false,
                'user' => $userDTO->except(...self::$excludedUserFields)->toArray(),
                'message' => Loc::getMessage('nothing_to_edit')
            ];
        }

        $userDTO->modify($body);

        $update = $this->userRepository->update($userDTO);

        return [
            'update'  => $update,
            'user'    => $this->userRepository->findById($userId)->get()->except(...self::$excludedUserFields)->toArray(),
            'message' => Loc::getMessage('SUCCESSFUL_EDITED_PROFILE'),
        ];
    }

    /**
     * @param int $userId
     *
     * @return array
     * @throws Exception
     */
    public function clearProfile(int $userId): array
    {
        $userDTO = $this->userRepository->findById($userId)->get();
        $userData = $userDTO->except()->toArray();

        if (empty($userData)) {
            return ['result' => false];
        }

        $addressData = $this->addressRepository->findByUserId($userId)->all();
        if($addressData) {
            foreach ($addressData as $addressItem) {
                UserAddressTable::delete($addressItem->id);
            }
        }

        $body = [
            'name' => null,
            'secondName' => null,
            'lastName' => null,
            'email' => null,
            'gender' => null,
            'birthdate' => null,
            'maritalStatus' => null,
            'skinType' => null,
            'subscribedToEmail' => false,
            'subscribedToPush' => false,
            'subscribedToSms' => false,
            'cityId' => null,
            'favoriteStore' => null,
            'emailConfirmed' => false
        ];

        $userDTO->modify($body);
        if($this->userRepository->update($userDTO)) {
            return ['result' => true];
        }

        return ['result' => false];
    }

    public function exportResult(string $message = null): array
    {
        return [];
    }

    /**
     * @param int      $userId
     * @param int|null $id
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getAddress(int $userId, int $id = null): array
    {
        $result = [];

        if ($id !== null) {
            AddressRepository::validateId($id);
            AddressRepository::validateUserAddress($id, $userId);
            /**
             * @var AddressDTO $addressDTO
             */
            $addressDTO = $this->addressRepository->findById($id)->get();

            $result['address'] = $addressDTO->except('userId', 'fullAddress')->toArray();
            return $result;
        }

        $result['list'] = $this->addressRepository->findByUserId($userId)->all();
        return $result;
    }

    /**
     * @param int   $userId
     * @param array $body
     *
     * @return array
     *
     * @throws Exception
     */
    public function addAddress(int $userId, array $body): array
    {
        $this->prepareEmptyValues($body);

        $this->addressValidator->setRequestBody($body);
        $this->addressValidator->validateRequiredFields();
        $this->addressValidator->validateOnEmptyValues();

        $addressDTO = $this->addressRepository->create(AddressDTOFactory::createFromRequestBody($userId, $body));

        return [
            'created' => true,
            'address' => $addressDTO->except('userId')->toArray(),
            'message' => Loc::getMessage('SUCCESSFUL_CREATED_ADDRESS'),
        ];
    }

    /**
     * @param int   $userId
     * @param array $body
     * @param int   $id
     *
     * @return array
     *
     * @throws Exception
     */
    public function editAddress(int $userId, array $body, int $id): array
    {
        AddressRepository::validateId($id);

        AddressRepository::validateUserAddress($id, $userId);

        $this->prepareEmptyValues($body);

        $this->addressValidator->setRequestBody($body);
        $this->addressValidator->validateOnEmptyValues();

        /**
         * @var AddressDTO $addressDTO
         */
        $addressDTO = $this->addressRepository->findById($id)->get();
        if(!$body['name']) {
            $body['name'] = ($addressDTO->name ?: $addressDTO->fullAddress);
        }
        $addressDTO->modify($body);
        $update     = $this->addressRepository->update($addressDTO);

        return [
            'updated' => $update,
            'address' => $addressDTO->except('userId')->toArray(),
            'message' => Loc::getMessage('SUCCESSFUL_UPDATED_ADDRESS'),
        ];
    }

    /**
     * @param int $userId
     * @param int $id
     *
     * @return array
     *
     * @throws Exception
     */
    public function deleteAddress(int $userId, int $id): array
    {
        AddressRepository::validateId($id);
        AddressRepository::validateUserAddress($id, $userId);

        /**
         * @var AddressDTO $addressDTO
         */
        $addressDTO = $this->addressRepository->findById($id)->get();
        $delete     = $this->addressRepository->delete($addressDTO);

        return [
            'deleted' => $delete,
            'message' => Loc::getMessage('SUCCESSFUL_DELETED_ADDRESS'),
        ];
    }

    /**
     * @param int   $userId
     * @param array $body
     *
     * @return array
     *
     * @throws Exception
     */
    public function editNotifications(int $userId, array $body): array
    {
        $userDTO = $this->userRepository->findById($userId)->get();

        ServiceException::validateEmailNotifications($userDTO, $body['subscribedToEmail']);

        $userDTO->modify($body);

        $update  = $this->userRepository->setNotifications($userDTO);
        $updated = $this->userRepository->findById($userId)->get();

        return [
            'updated'       => $update,
            'notifications' => [
                'subscribedToEmail' => $updated->subscribedToEmail,
                'subscribedToPush'  => $updated->subscribedToPush,
                'subscribedToSms'   => $updated->subscribedToSms,
            ],
            'message'       => Loc::getMessage('SUCCESSFUL_UPDATED_NOTIFICATIONS'),
        ];
    }

    private function prepareBody(array &$body)
    {
        if (array_key_exists('id', $body)) {
            unset($body['id']);
        }
    }

    /**
     * Редактирование email
     *
     * @param int   $userId
     * @param array $body
     *
     * @return array
     *
     * @throws Exception
     */
    public function changeEmail(int $userId, array $body): array
    {
        $this->userValidator->setUserId($userId)->setRequestBody($body)->validateEmail();

        $userDTO = $this->userRepository->findById($userId)->get()->modify($body);

        $update = $this->userRepository->changeEmail($userDTO);

        return [
            'updated' => $update,
            'user'    => $this->userRepository->findById($userId)->get()->except('id', 'fuserId', 'login')->toArray(),
            'message' => Loc::getMessage('successful_change_email'),
        ];
    }

    private function prepareEmptyValues(array &$body)
    {
        foreach ($body as $key => &$value) {
            if (gettype($value) === 'string' && ! in_array($key, $this->addressValidator->requiredFields) && strlen(
                    preg_replace('/\s+/u', '', $value)
                ) === 0) {
                $value = null;
            }
        }
    }
}
