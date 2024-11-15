<?php

namespace NaturaSiberica\Api\Repositories\User;

use Bitrix\Catalog\StoreTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Internals\FuserTable;
use CUser;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Mindbox\Exceptions\MindboxException;
use NaturaSiberica\Api\DTO\User\UserDTO;
use NaturaSiberica\Api\Entities\FieldEnumTable;
use NaturaSiberica\Api\Entities\User\PhoneAuthTable;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Interfaces\Services\Mindbox\User\ProfileServiceInterface;
use NaturaSiberica\Api\Interfaces\Services\Token\TokenServiceInterface;
use NaturaSiberica\Api\Interfaces\Services\User\PhoneServiceInterface;
use NaturaSiberica\Api\Services\Mindbox\User\ProfileService;
use NaturaSiberica\Api\Services\User\PhoneService;
use NaturaSiberica\Api\Tools\Settings\Options;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;
use NaturaSiberica\Api\Validators\DTOValidator;

Loc::loadMessages(__FILE__);

class UserRepository
{
    use HighloadBlockTrait;

    private CUser $CUser;
    private Query $query;

    private PhoneServiceInterface   $phoneService;
    private ProfileServiceInterface $profileService;

    /**
     * @var UserDTO[]
     */
    private array $collection = [];

    private array $select = [
        'ID',
        'NAME',
        'SECOND_NAME',
        'LAST_NAME',
        'LOGIN',
        'EMAIL',
        'FUSER_ID'                => 'FUSER.ID',
        'BIRTHDATE'               => 'PERSONAL_BIRTHDAY',
        'GENDER'                  => 'PERSONAL_GENDER',
        'CITY_ID'                 => 'UF_CITY_ID', // Город (из справочника)
        'CITY'                    => 'C.UF_NAME',
        'PHONE'                   => 'PHONE_AUTH.PHONE_NUMBER',
        'FAVORITE_STORE'          => 'UF_FAVOURITE_STORE',
        'FAVORITE_STORE_ADDRESS'  => 'FS.ADDRESS',
        'LOYALTY_CARD'            => 'UF_LOYALTY_CARD', // Номер карты лояльности
        'BONUSES'                 => 'UF_BONUSES', // Бонусы
        'PERSONAL_DISCOUNT'       => 'UF_PERSONAL_DISCOUNT', // Размер персональной скидки по карте
        'STATUS'                  => 'UF_STATUS', // Статус
        'MARITAL_STATUS'          => 'UF_MARITAL_STATUS', // Семейное положение
        'MARITAL_STATUS_VALUE'    => 'MS.VALUE',
        'SKIN_TYPE'               => 'UF_SKIN_TYPE', // Тип кожи
        'SUBSCRIBED_TO_EMAIL'     => 'UF_EMAIL_SUBSCRIBE', // Подписка на рассылку уведомлений по email
        'SUBSCRIBED_TO_PUSH'      => 'UF_PUSH_SUBSCRIBE', // Подписка на рассылку push уведомлений
        'SUBSCRIBED_TO_SMS'       => 'UF_SMS_SUBSCRIBE', // Подписка на рассылку уведомлений по смс
        'UID'                     => 'UF_UID', // Уникальный идентификатор для расшаривания избранного
        'MINDBOX_ID'              => 'UF_MINDBOX_ID', // ID клиента в Mindbox
        'MINDBOX_PHONE_CONFIRMED' => 'UF_PHONE_CONFIRMED', // Флаг подтверждения телефона в Mindbox
        'EMAIL_CONFIRMED' => 'UF_EMAIL_CONFIRMED', // Флаг подтверждения телефона в Mindbox
    ];

    private array $addFields = [
        'PHONE'               => 'PHONE_NUMBER',
        'BIRTHDATE'           => 'PERSONAL_BIRTHDAY',
        'CITY_ID'             => 'UF_CITY_ID',
        'SKIN_TYPE'           => 'UF_SKIN_TYPE',
        'FAVORITE_STORE'      => 'UF_FAVOURITE_STORE',
        'MARITAL_STATUS'      => 'UF_MARITAL_STATUS',
        'GENDER'              => 'PERSONAL_GENDER',
        'SUBSCRIBED_TO_PUSH'  => 'UF_PUSH_SUBSCRIBE',
        'SUBSCRIBED_TO_EMAIL' => 'UF_EMAIL_SUBSCRIBE',
        'SUBSCRIBED_TO_SMS'   => 'UF_SMS_SUBSCRIBE',
        'MINDBOX_ID'          => 'UF_MINDBOX_ID',
        'EMAIL_CONFIRMED'     => 'UF_EMAIL_CONFIRMED',
    ];

    public array $interrogableRequiredFields = [
        'name',
        'lastName',
        'email',
        'gender',
        'cityId',
    ];

    public array  $genders = ['M', 'F'];
    private array $runtime = [];

    /**
     * @throws ArgumentException
     * @throws MindboxException
     * @throws SystemException
     */
    public function __construct()
    {
        $this->setCUser(new CUser());
        $this->setRuntime();
        $this->setQuery(UserTable::query());

        $this->phoneService   = new PhoneService($this);
        $this->profileService = new ProfileService($this);
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @param Query $query
     *
     * @return void
     * @throws ArgumentException
     * @throws SystemException
     */
    private function setQuery(Query $query): void
    {
        $query->setSelect($this->select);

        foreach ($this->runtime as $field) {
            $query->registerRuntimeField($field);
        }

        $this->query = $query;
    }

    /**
     * @param Reference[] $references
     * @param array       $referenceSelectFields
     *
     * @return UserRepository
     * @throws ArgumentException
     * @throws SystemException
     */
    public function addRuntimeFields(array $references, array $referenceSelectFields = []): UserRepository
    {
        foreach ($references as $reference) {
            $this->query->registerRuntimeField($reference);
        }
        $this->query->setSelect(array_merge($this->select, $referenceSelectFields));

        return $this;
    }

    /**
     * @param DTOInterface|null $object
     *
     * @return UserDTO|DTOInterface|null
     * @throws ArgumentNullException
     * @throws Exception
     */
    public function create(DTOInterface $object)
    {
        /**
         * @var UserDTO $object
         */

        $object->subscribedToPush = true;
        $object->subscribedToSms  = true;

        $findCustomer = $this->profileService->findCustomer($object);

        if ($findCustomer['customer']['processingStatus'] === ProfileServiceInterface::RESPONSE_PROCESSING_STATUS_CUSTOMER_FOUND) {
            $object->mindboxId = $findCustomer['customer']['ids']['mindboxId'];
        }

        $fields = $this->prepareOrmFields($object->convertToBitrixFormat());

        $id = $this->CUser->Add($fields);

        if ($id <= 0) {
            throw new Exception(strip_tags($this->CUser->LAST_ERROR), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }
        $object->id = $id;

        if (Options::isEnabledSendDataInMindbox()) {
            if ($findCustomer['customer']['processingStatus'] === ProfileServiceInterface::RESPONSE_PROCESSING_STATUS_CUSTOMER_NOT_FOUND) {
                $registerCustomer = $this->profileService->registerCustomer($object);

                if ($registerCustomer['customer']['processingStatus'] === ProfileServiceInterface::RESPONSE_PROCESSING_STATUS_CUSTOMER_CREATED) {
                    $object->mindboxId = $registerCustomer['customer']['ids']['mindboxId'];
                    $this->setMindboxId($object->id, $object->mindboxId);
                }
            }

            $confirmPhoneInMindbox = $this->profileService->confirmPhone($object);

            if ($confirmPhoneInMindbox['mobilePhoneConfirmation']['processingStatus'] === ProfileServiceInterface::RESPONSE_PROCESSING_STATUS_PHONE_CONFIRMED) {
                $object->mindboxPhoneConfirmed = $this->confirmPhoneInMindbox($id);
            }

            $this->profileService->editNotifications($object);
        }

        return $object;
    }

    public function prepareOrmFields(array $data): array
    {
        $preparedFields = [];

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $this->addFields)) {
                $field                  = $this->addFields[$key];
                $preparedFields[$field] = $value;
                unset($data[$key]);
            }
        }

        $password = uniqid('password.');

        $preparedFields['PASSWORD']         = $password;
        $preparedFields['CONFIRM_PASSWORD'] = $password;

        return array_merge_recursive($preparedFields, $data);
    }

    /**
     * @param UserDTO $userDTO
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function setNotifications(UserDTO $userDTO)
    {
        $fields = $userDTO->only('subscribedToEmail', 'subscribedToPush', 'subscribedToSms')->convertToBitrixFormat();
        $this->prepareFieldsForUpdate($fields);

        $updateNotifications = $this->CUser->Update($userDTO->id, $fields);

        if (! $updateNotifications) {
            throw new Exception(strip_tags($this->CUser->LAST_ERROR), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        if (Options::isEnabledSendDataInMindbox()) {
            $this->profileService->editNotifications($userDTO);
        }

        return $updateNotifications;
    }

    /**
     * @param UserDTO $userDTO
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function changeEmail(UserDTO $userDTO)
    {
        $fields = $userDTO->only('email')->convertToBitrixFormat(true);

        $changeEmail = $this->CUser->Update($userDTO->id, $fields);

        if (! $changeEmail) {
            throw new Exception(strip_tags($this->CUser->LAST_ERROR), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        if (Options::isEnabledSendDataInMindbox()) {
            $this->profileService->editProfile($userDTO);
        }

        return $changeEmail;
    }

    /**
     * @param DTOInterface $object
     *
     * @return bool
     * @throws Exception
     */
    public function update(DTOInterface $object): bool
    {
        /**
         * @var UserDTO $object
         */
        DTOValidator::assertPropertyNotNull('id', $object);

        $fields = $object->convertToBitrixFormat(true);
        $this->prepareFieldsForUpdate($fields, false);

        $update = $this->CUser->Update($object->id, $fields);

        if ($update) {
            if (Options::isEnabledSendDataInMindbox()) {
                $this->profileService->editProfile($object);
            }
            return true;
        }

        throw new Exception(strip_tags($this->CUser->LAST_ERROR), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param CUser $CUser
     *
     * @return void
     */
    private function setCUser(CUser $CUser): void
    {
        $this->CUser = $CUser;
    }

    /**
     * @return void
     *
     * @throws ArgumentException
     * @throws SystemException
     */
    private function setRuntime(): void
    {
        $this->runtime = [
            new Reference('MS', FieldEnumTable::class, Join::on('this.UF_MARITAL_STATUS', 'ref.ID')),
            new Reference('ST', FieldEnumTable::class, Join::on('this.UF_SKIN_TYPE', 'ref.ID')),
            new Reference('FUSER', FuserTable::class, Join::on('this.ID', 'ref.USER_ID')),
            new Reference('FS', StoreTable::class, Join::on('this.FAVORITE_STORE', 'ref.ID')),
            new Reference(
                'C', $this->getHlEntityByEntityName(ConstantEntityInterface::HLBLOCK_CITY)->getDataClass(), Join::on('this.CITY_ID', 'ref.ID')
            ),
        ];
    }

    private function prepareCollection()
    {
        $items = $this->query->fetchAll();

        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            $this->collection[] = UserDTO::convertFromBitrixFormat($item);
        }
    }

    public function all(): array
    {
        if (empty($this->collection)) {
            $this->prepareCollection();
        }

        return $this->collection;
    }

    public function get(): ?UserDTO
    {
        if (empty($this->collection)) {
            $this->prepareCollection();
        }
        return $this->collection[0];
    }

    public function addFilter($column, $value, bool $whereLike = false): UserRepository
    {
        if ($whereLike) {
            $this->query->whereLike($column, $value);
        } else {
            $this->query->where($column, '=', $value);
        }

        return $this;
    }

    public function findBy($field, $value): UserRepository
    {
        $this->query->addFilter($field, $value);
        $this->prepareCollection();
        return $this;
    }

    public function findByEmail(string $email): UserRepository
    {
        return $this->findBy('EMAIL', $email);
    }

    public function findById(int $id): UserRepository
    {
        return $this->findBy('ID', $id);
    }

    /**
     * @param string $login
     *
     * @return $this
     */
    public function findByLogin(string $login): UserRepository
    {
        return $this->findBy('LOGIN', $login);
    }

    /**
     * @param string $phone
     *
     * @return $this
     */
    public function findByPhone(string $phone): UserRepository
    {
        return $this->findBy('PHONE', $phone);
    }

    /**
     * @param string $uid
     *
     * @return $this
     */
    public function findByUid(string $uid): UserRepository
    {
        return $this->findBy('UF_UID', $uid);
    }

    protected function prepareFieldsForUpdate(&$fields, $removeId = true)
    {
        foreach ($fields as $field => &$value) {
            if (array_key_exists($field, $this->addFields)) {
                $fields[$this->addFields[$field]] = $value;
            }

            if ($value === null) {
                $fields[$field] = '';
            }

            if ($field === 'ID' && $removeId) {
                unset($fields[$field]);
            }

            if ($value instanceof Date) {
                $value = $value->format(TokenServiceInterface::DEFAULT_DATETIME_FORMAT);
            }
        }

        return $fields;
    }

    /**
     * @return array
     */
    public function getPropertiesTitles(): array
    {
        return [
            'name'       => Loc::getMessage('REQUIRED_PROPERTY_NAME'),
            'secondName' => Loc::getMessage('REQUIRED_PROPERTY_SECOND_NAME'),
            'lastName'   => Loc::getMessage('REQUIRED_PROPERTY_LAST_NAME'),
            'email'      => 'Email',
            'gender'     => Loc::getMessage('REQUIRED_PROPERTY_GENDER'),
            'cityId'     => Loc::getMessage('REQUIRED_PROPERTY_CITY'),
            'phone'      => Loc::getMessage('REQUIRED_PROPERTY_PHONE'),
        ];
    }

    /**
     * @param string $property
     *
     * @return string|null
     */
    public function getPropertyTitle(string $property): ?string
    {
        $propertiesTitles = $this->getPropertiesTitles();
        return $propertiesTitles[$property];
    }

    private function getUser(int $userId)
    {
        return CUser::GetByID($userId)->Fetch();
    }

    /**
     * Получение количества попыток входа
     *
     * @param int $userId
     *
     * @return false|int
     */
    public function getLoginAttempts(int $userId)
    {
        $user = $this->getUser($userId);
        return ! empty($user) ? (int)$user['LOGIN_ATTEMPTS'] : false;
    }

    /**
     * Запись в БД попытки входа
     *
     * @param int $userId
     *
     * @return false|int
     * @throws Exception
     */
    public function addLoginAttempt(int $userId)
    {
        $loginAttempts = $this->getLoginAttempts($userId);

        if (! is_numeric($loginAttempts)) {
            return false;
        }

        $addLoginAttempts = $this->CUser->Update($userId, [
            'LOGIN_ATTEMPTS' => ++$loginAttempts,
        ]);

        if (! $addLoginAttempts) {
            throw new Exception($this->CUser->LAST_ERROR, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        return $loginAttempts;
    }

    /**
     * Сброс в БД попыток входа
     *
     * @param int $userId
     *
     * @return bool
     * @throws Exception
     */
    public function resetLoginAttempts(int $userId): bool
    {
        $userDTO = $this->findById($userId)->get();
        $reset   = $this->CUser->Update($userId, ['LOGIN_ATTEMPTS' => 0]);

        if (! $reset) {
            throw new Exception($this->CUser->LAST_ERROR, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        PhoneAuthTable::reset($userDTO->phone);

        return $reset;
    }

    public function isBlocked(int $userId): bool
    {
        $user = $this->getUser($userId);
        return $user['BLOCKED'] === 'Y';
    }

    /**
     * @param int $userId
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function blockUser(int $userId)
    {
        $blockUser = $this->CUser->Update($userId, [
            'BLOCKED' => 'Y',
        ]);

        if (! $blockUser) {
            throw new Exception($this->CUser->LAST_ERROR, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        return $blockUser;
    }

    /**
     * @param int $userId
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function unblockUser(int $userId)
    {
        $unblockUser = $this->CUser->Update($userId, [
            'BLOCKED' => 'N',
        ]);

        if (! $unblockUser) {
            throw new Exception($this->CUser->LAST_ERROR, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        return $unblockUser;
    }

    /**
     * @param int $userId
     *
     * @return bool|mixed
     * @throws Exception
     */
    public function confirmPhoneInMindbox(int $userId)
    {
        $confirmPhone = $this->CUser->Update($userId, [
            'UF_PHONE_CONFIRMED' => true,
        ]);

        if (! $confirmPhone) {
            throw new Exception($this->CUser->LAST_ERROR, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        return $confirmPhone;
    }

    public function setMindboxId(int $userId, int $mindboxId)
    {
        $setMindboxId = $this->CUser->Update($userId, [
            'UF_MINDBOX_ID' => $mindboxId,
        ]);

        if (! $setMindboxId) {
            throw new Exception($this->CUser->LAST_ERROR, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        return $setMindboxId;
    }
}
