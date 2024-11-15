<?php

namespace NaturaSiberica\Api\Validators\User;

use Bitrix\Catalog\StoreTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\UserFieldTable;
use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Fuser;
use DateTime;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\Error\ErrorDTO;
use NaturaSiberica\Api\DTO\User\UserDTO;
use NaturaSiberica\Api\Entities\FieldEnumTable;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Repositories\CityRepository;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Services\Mindbox\User\ProfileService;
use NaturaSiberica\Api\Services\User\AuthService;
use NaturaSiberica\Api\Tools\Settings\Options;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;
use NaturaSiberica\Api\Validators\UserField\UserFieldValidator;
use NaturaSiberica\Api\Validators\Validator;

Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/lib/userphoneauth.php');
Loc::loadMessages(dirname(__DIR__, 2) . '/services/user/authservice.php');
Loc::loadMessages(dirname(__DIR__, 2) . '/services/user/phoneservice.php.php');
Loc::loadMessages(dirname(__DIR__, 2) . '/repositories/user/userrepository.php');

class UserValidator extends Validator implements ConstantEntityInterface
{
    use HighloadBlockTrait;

    const DATETIME_MODIFIER_PLUS_HUNDRED_YEARS = '+ 100 years';
    const DATE_PARTS_COUNT                     = 3;

    private UserRepository $userRepository;
    private ProfileService $profileService;
    private ?AuthService   $authService = null;

    private ?int $userId = null;

    private array $userFields = [
        'favoriteStore' => 'UF_FAVOURITE_STORE',
        'skinType'      => 'UF_SKIN_TYPE',
        'maritalStatus' => 'UF_MARITAL_STATUS',
        'cityId'        => 'UF_CITY_ID',
    ];

    /**
     * @param int|null $userId
     *
     * @return UserValidator
     */
    public function setUserId(?int $userId): UserValidator
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @param AuthService|null $authService
     */
    public function setAuthService(?AuthService $authService): void
    {
        $this->authService = $authService;
    }

    public function __construct(UserRepository $userRepository = null, AuthService $authService = null)
    {
        if ($userRepository instanceof UserRepository) {
            $this->setUserRepository($userRepository);
            $this->profileService = new ProfileService($userRepository);
        }

        if ($authService instanceof AuthService) {
            $this->setAuthService($authService);
        }

        parent::__construct();
    }

    /**
     * Проверяет, зарегистрирован ли пользователь в системе
     * Используется при регистрации
     *
     * @see AuthService::register()
     *
     * @param UserRepository $repository
     * @param UserDTO        $dto
     *
     * @return void
     * @throws Exception
     */
    public function validateRegisteredUser(UserRepository $repository, UserDTO $dto)
    {
        if ($dto->id !== null || $repository->findByPhone($dto->phone)->get()) {
            throw new Exception(
                Loc::getMessage('ERROR_PHONE_ALREADY_EXISTS', [
                    '#PHONE#' => $dto->phone,
                ]), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * Проверяет, зарегистрирован ли пользователь в системе
     * Используется при авторизации
     *
     * @param UserDTO|null $userDto
     *
     * @return void
     * @throws Exception
     * @see AuthService::login()
     *
     */
    public function validateUnRegisteredUser(UserDTO $userDto = null)
    {
        if ($userDto === null) {
            throw new Exception(Loc::getMessage('error_unregistered_user'), StatusCodeInterface::STATUS_UNAUTHORIZED);
        }
    }

    /**
     * @param UserRepository|null $userRepository
     */
    private function setUserRepository(?UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Проверяет пользователя по номеру телефона
     *
     * @param string             $phone
     * @param UserDTO|false|null $userDTO
     *
     * @return void
     *
     * @throws Exception
     * @see AuthService::login()
     *
     */
    public function validatePhoneInDTO(string $phone, $userDTO = null)
    {
        if (! ($userDTO instanceof UserDTO) || $userDTO->phone !== $phone) {
            throw new Exception(
                Loc::getMessage('ERROR_USER_BY_PHONE_NOT_FOUND', [
                    '#PHONE#' => $phone,
                ]), StatusCodeInterface::STATUS_UNAUTHORIZED
            );
        }
    }

    /**
     * @param array $user
     *
     * @return $this
     *
     * @throws Exception
     */
    public function validateRequiredFieldsBeforeAdd(array $user): UserValidator
    {
        foreach ($this->userRepository->interrogableRequiredFields as $requiredField) {
            if (empty($user[$requiredField]) && empty($this->requestBody[$requiredField])) {
                $type     = sprintf('empty_%s', $requiredField);
                $message  = Loc::getMessage('error_empty_required_field', [
                    '#field#' => $this->userRepository->getPropertyTitle($requiredField),
                ]);
                $errorDto = ErrorDTO::createFromParameters($type, $this->errorCode, $message);
                $this->addCustomError($errorDto);
            }
        }

        $this->throwErrors();

        return $this;
    }

    /**
     * @param array $user
     *
     * @return $this
     *
     * @throws Exception
     */
    public function validateRequiredFieldsBeforeUpdate(array $user): UserValidator
    {
        foreach ($this->userRepository->interrogableRequiredFields as $requiredField) {
            if (! empty($user[$requiredField]) && (array_key_exists(
                        $requiredField,
                        $this->requestBody
                    ) && empty($this->requestBody[$requiredField]))) {
                $type     = sprintf('%s_can_not_be_update', $requiredField);
                $message  = Loc::getMessage('error_update_required_field', [
                    '#field#' => $this->userRepository->getPropertyTitle($requiredField),
                ]);
                $errorDto = ErrorDTO::createFromParameters($type, $this->errorCode, $message);
                $this->addCustomError($errorDto);
            }
        }

        $this->throwErrors();

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function validateEmail(): UserValidator
    {
        if (isset($this->requestBody['email']) && empty($this->requestBody['email'])) {
            throw new Exception(Loc::getMessage('error_empty_email'), $this->errorCode);
        }

        if (! empty($this->requestBody['email'])) {
            $this->validateCorrectEmail();
            $this->validateEmailLength();;
            $this->validateEmailOnUnique();
        }

        return $this;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function validateCorrectEmail()
    {
        if (! filter_var($this->requestBody['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception(Loc::getMessage('error_incorrect_email'), $this->errorCode);
        }
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function validateBirthdate(): UserValidator
    {
        if (! isset($this->requestBody['birthdate'])) {
            return $this;
        }
        $birthdate = $this->requestBody['birthdate'];

        $explodedDate           = explode('.', $birthdate);
        $currentDateObject      = new DateTime();
        $dayAndMonthDigitsCount = 2;
        $validYearDigitsCount   = 4;

        if (count($explodedDate) < self::DATE_PARTS_COUNT || strlen($explodedDate[2]) !== $validYearDigitsCount || strlen(
                $explodedDate[0]
            ) > $dayAndMonthDigitsCount || strlen($explodedDate[1]) > $dayAndMonthDigitsCount) {
            throw new Exception(Loc::getMessage('error_incorrect_birthdate_format'), $this->errorCode);
        }

        $ageInSeconds = time() - strtotime($birthdate);

        if ($ageInSeconds < ModuleInterface::FIVE_YEARS_IN_SECONDS || $ageInSeconds > ModuleInterface::HUNDRED_YEARS_IN_SECONDS || (int)$currentDateObject->modify(
                self::DATETIME_MODIFIER_PLUS_HUNDRED_YEARS
            )->format('Y') < (int)$explodedDate[2]) {
            throw new Exception(
                Loc::getMessage('ERROR_INCORRECT_AGE'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
            );
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function validateGender(): UserValidator
    {
        if (isset($this->requestBody['gender']) && ! in_array($this->requestBody['gender'], $this->userRepository->genders)) {
            throw new Exception(
                Loc::getMessage('ERROR_INCORRECT_GENDER'), $this->errorCode
            );
        }

        return $this;
    }

    /**
     * Проверяет тело запроса перед редактированием профиля
     *
     * @param array      $user
     * @param array|null $requestBody
     *
     * @return $this
     *
     * @throws Exception
     * @see \NaturaSiberica\Api\Services\User\ProfileService::editProfile()
     *
     */
    public function validate(array $user, array $requestBody = null): UserValidator
    {
        if ((int)$user['id'] > 0) {
            $this->setUserId((int)$user['id']);
        }

        $this->setRequestBody($requestBody)
             ->validateRequiredFieldsBeforeAdd($user)
             ->validateRequiredFieldsBeforeUpdate($user)
             ->validateEmail()
             ->validateBirthdate()
             ->validateCity()
             ->validateGender()
             ->validateMaritalStatus()
             ->validateSkinType()
             ->validateFavoriteStore()
             ->validateMaxStringLength();

        return $this;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function validateEmailOnUnique()
    {
        if (empty($this->requestBody['email'])) {
            return;
        }

        $user = UserTable::getList([
            'filter' => ['=EMAIL' => $this->requestBody['email']],
            'select' => ['ID', 'EMAIL'],
        ])->fetch();

        if (! empty($user) && $this->userId !== (int)$user['ID']) {
            throw new Exception(Loc::getMessage('error_email_not_unique'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param array $body
     *
     * @return void
     *
     * @throws Exception
     */
    public static function validateEmptyPhone(array $body)
    {
        if ($body['phone'] === null) {
            throw new Exception(Loc::getMessage('error_empty_phone_number'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @param $phone
     *
     * @return void
     *
     * @throws Exception
     */
    public static function validatePhone($phone)
    {
        $validate        = UserPhoneAuthTable::validatePhoneNumber($phone);
        $normalizedPhone = UserPhoneAuthTable::normalizePhoneNumber($phone);

        if ($phone !== $normalizedPhone || $validate !== true) {
            throw new Exception(Loc::getMessage('user_phone_auth_err_incorrect_number'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    /**
     * @return $this
     *
     * @throws Exception
     */
    public function validateMaxStringLength(): UserValidator
    {
        $length = 30;

        foreach ($this->requestBody as $key => $value) {
            if (gettype($value) === 'string' && mb_strlen($value) > $length && ! in_array($key, ['email', 'uid'])) {
                $type     = sprintf('%s_incorrect_length', $key);
                $message  = Loc::getMessage('error_string_length', [
                    '#field#' => $this->userRepository->getPropertyTitle($key),
                ]);
                $errorDto = ErrorDTO::createFromParameters($type, $this->errorCode, $message);
                $this->addCustomError($errorDto);
            }
        }

        $this->throwErrors();

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function validateEmailLength(): UserValidator
    {
        $explodedEmail      = explode('@', $this->requestBody['email']);
        $maxEmailNameLength = 64;
        $maxEmailLength     = 256;

        if (strlen($explodedEmail[0]) > $maxEmailNameLength || strlen($this->requestBody['email']) > $maxEmailLength) {
            throw new Exception(
                Loc::getMessage('ERROR_EMAIL_LENGTH'), $this->errorCode
            );
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function validateSkinType(): UserValidator
    {
        if (isset($this->requestBody['skinType'])) {
            $this->validateEnumField('skinType', $this->requestBody['skinType']);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function validateMaritalStatus(): UserValidator
    {
        if (isset($this->requestBody['maritalStatus'])) {
            $this->validateEnumField('maritalStatus', $this->requestBody['maritalStatus']);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function validateFavoriteStore(): UserValidator
    {
        $query = StoreTable::query()->addFilter('ISSUING_CENTER', 'Y');

        if (isset($this->requestBody['favoriteStore']) && ! is_numeric($this->requestBody['favoriteStore'])) {
            throw new Exception(
                Loc::getMessage('error_value_not_number', [
                    '#field#' => 'favoriteStore',
                ]), $this->errorCode
            );
        }

        if (isset($this->requestBody['favoriteStore']) && ! UserFieldValidator::checkValue(
                $query,
                'ID',
                $this->requestBody['favoriteStore']
            )) {
            throw new Exception(
                Loc::getMessage('ERROR_INCORRECT_ID', [
                    '#FIELD#' => 'favoriteStore',
                ]), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
            );
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function validateCity(): UserValidator
    {
        if (! empty($this->requestBody['cityId']) && ! CityRepository::check($this->requestBody['cityId'])) {
            throw new Exception(
                Loc::getMessage('error_city_not_found', ['#id#' => $this->requestBody['cityId']]), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
            );
        }

        return $this;
    }

    /**
     * @param string $field
     * @param        $valueId
     *
     *
     * @return $this
     * @throws Exception
     */
    protected function validateEnumField(string $field, $valueId): UserValidator
    {
        $query = $this->prepareEnumFieldQuery($this->userFields[$field]);

        if (! UserFieldValidator::checkValue($query, 'ID', $valueId)) {
            throw new Exception(
                Loc::getMessage('ERROR_INCORRECT_ID', [
                    '#FIELD#' => $field,
                ]), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
            );
        }

        return $this;
    }

    /**
     * @param int|null $userId
     *
     * @return void
     * @throws Exception
     */
    public static function validateUser(int $userId = null, string $message = null, int $code = StatusCodeInterface::STATUS_UNAUTHORIZED)
    {
        if ($userId === null || $userId === 0) {
            throw new Exception($message ?? Loc::getMessage('ERROR_UNAUTHORIZED_USER'), $code);
        }
    }

    /**
     * @param int|null    $fuserId
     * @param string|null $message
     *
     * @return void
     * @throws Exception
     */
    public static function validateFuser(int $fuserId = null, string $message = null)
    {
        if ($fuserId === null) {
            throw new Exception($message ?? Loc::getMessage('ERROR_UNKNOWN_FUSER'), StatusCodeInterface::STATUS_UNAUTHORIZED);
        }
    }

    /**
     * @param string $fieldName
     *
     * @return Query
     * @throws Exception
     */
    protected function prepareEnumFieldQuery(string $fieldName): Query
    {
        $query = FieldEnumTable::query();
        $query->registerRuntimeField(
            new Reference(
                'UF', UserFieldTable::class, Join::on('this.USER_FIELD_ID', 'ref.ID')
            )
        );
        $query->addFilter('UF.FIELD_NAME', $fieldName);
        $query->setSelect(['ID', 'USER_FIELD_ID', 'FIELD_NAME' => 'UF.FIELD_NAME', 'VALUE']);

        return $query;
    }

    /**
     * @param int         $fuserId
     * @param int         $userId
     * @param string|null $message
     *
     * @return void
     * @throws Exception
     */
    public static function validateFuserByUser(int $fuserId, int $userId, string $message = null)
    {
        $fuserByUserId = (int)Fuser::getIdByUserId($userId);

        if ($fuserId !== $fuserByUserId) {
            throw new Exception($message ?? Loc::getMessage('ERROR_UNKNOWN_FUSER'), StatusCodeInterface::STATUS_FORBIDDEN);
        }
    }

    /**
     * @param int $userId
     *
     * @return void
     * @throws Exception
     */
    public function validateLoginAttempts(int $userId)
    {
        if ($this->userRepository->getLoginAttempts($userId) >= Options::getLoginAttempts()) {
            $this->authService->blockUser($userId);
            $phone = $this->userRepository->findById($userId)->get()->phone;
            $this->authService->blockPhone($phone);
            throw new Exception(Loc::getMessage('error_blocked_user'), StatusCodeInterface::STATUS_FORBIDDEN);
        }
    }

    /**
     * @param int $userId
     *
     * @return void
     * @throws Exception
     */
    public function validateBlockedUser(int $userId)
    {
        if ($this->userRepository->isBlocked($userId)) {
            throw new Exception(Loc::getMessage('error_blocked_user'), StatusCodeInterface::STATUS_FORBIDDEN);
        }
    }

    /**
     * @param string $phone
     *
     * @return void
     * @throws Exception
     */
    public function validateBlockedPhone(string $phone)
    {
        if ($this->authService->isPhoneBlocked($phone)) {
            throw new Exception(Loc::getMessage('error_blocked_phone'), StatusCodeInterface::STATUS_FORBIDDEN);
        }
    }

    /**
     * @param string $phone
     *
     * @return bool
     */
    public function validateMindboxCustomerByPhone(string $phone): bool
    {
        $userDto = UserDTO::createFromPhone($phone);
        return $this->profileService->isMindboxCustomer($userDto);
    }
}
