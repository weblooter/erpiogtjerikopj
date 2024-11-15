<?php

namespace NaturaSiberica\Api\Services\User;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserPhoneAuthTable;
use CAgent;
use CSaleBasket;
use CSaleUser;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\User\FuserDTO;
use NaturaSiberica\Api\DTO\User\UserDTO;
use NaturaSiberica\Api\Entities\User\PhoneAuthTable;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Exceptions\TokenException;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Interfaces\Services\Mindbox\User\ProfileServiceInterface;
use NaturaSiberica\Api\Interfaces\Services\Token\TokenServiceInterface;
use NaturaSiberica\Api\Interfaces\Services\User\AuthServiceInterface;
use NaturaSiberica\Api\Interfaces\Services\User\PhoneServiceInterface;
use NaturaSiberica\Api\Repositories\Token\TokenRepository;
use NaturaSiberica\Api\Repositories\User\FuserRepository;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Services\Mindbox\User\ProfileService as MindboxProfileService;
use NaturaSiberica\Api\Services\Token\TokenService;
use NaturaSiberica\Api\Tools\Settings\Options;
use NaturaSiberica\Api\V2\Services\User\FavouritesService;
use NaturaSiberica\Api\Validators\User\UserValidator;
use ReflectionException;

Loc::loadMessages(__FILE__);
Loc::loadMessages(__DIR__ . '/phoneservice.php');
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/lib/controller/phoneauth.php');

class AuthService implements AuthServiceInterface
{

    protected TokenServiceInterface   $tokenService;
    protected PhoneServiceInterface   $phoneService;
    protected ProfileServiceInterface $profileService;
    protected UserRepository          $userRepository;
    protected FuserRepository         $fuserRepository;
    protected TokenRepository         $tokenRepository;
    protected UserValidator           $userValidator;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->userRepository  = new UserRepository();
        $this->fuserRepository = new FuserRepository();
        $this->tokenService    = new TokenService();
        $this->tokenRepository = new TokenRepository();
        $this->profileService  = new MindboxProfileService($this->userRepository);
        $this->phoneService    = new PhoneService($this->userRepository);
        $this->userValidator   = new UserValidator($this->userRepository, $this);
    }

    /**
     * @param string             $phone
     * @param UserDTO|false|null $user
     *
     * @throws Exception
     */
    public static function validateUser(string $phone, $user = null)
    {
        if (! ($user instanceof UserDTO) || $user->phone !== $phone) {
            throw new Exception(
                Loc::getMessage('ERROR_USER_BY_PHONE_NOT_FOUND', [
                    '#PHONE#' => $phone,
                ]), StatusCodeInterface::STATUS_UNAUTHORIZED
            );
        }
    }

    /**
     * @param int   $fuserId
     * @param array $body
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws ArgumentNullException
     * @throws ObjectPropertyException
     * @throws ReflectionException
     * @throws ServiceException
     * @throws SystemException
     * @throws TokenException
     * @throws Exception
     */
    public function register(int $fuserId, array $body): array
    {
        $this->tokenService->setFuserId($fuserId);

        UserValidator::validateEmptyPhone($body);
        UserValidator::validatePhone($body['phone']);

        $phone = UserPhoneAuthTable::normalizePhoneNumber($body['phone']);
        $code  = (string)$body['code'];
        $row   = PhoneAuthTable::getByPrimary($body['phone'])->fetchObject();

        if ($row) {
            $checkAttempts = (int)$row->get('REGISTER_ATTEMPTS') < Options::getLoginAttempts();

            if (! $checkAttempts) {
                $this->blockPhone($phone);
            }
        }

        $isPhoneBlocked = $this->isPhoneBlocked($phone);

        if ($isPhoneBlocked) {
            throw new Exception(
                Loc::getMessage('error_blocked_phone'), StatusCodeInterface::STATUS_FORBIDDEN
            );
        }

        $verifyCode = $this->phoneService->verifyCode($phone, $code, 'REGISTER_ATTEMPTS');

        if (! $verifyCode) {
            throw new Exception(
                Loc::getMessage('main_err_confirm'), StatusCodeInterface::STATUS_BAD_REQUEST
            );
        }

        $userDTO = UserDTO::createFromPhone($phone);
        $this->userValidator->validateRegisteredUser($this->userRepository, $userDTO);
        $userDTO = $this->userRepository->create($userDTO);
        $this->tokenService->setUserId($userDTO->id);
        
        /**
         * @var FuserDTO $fuserDTO
         */
        $fuserDTO         = $this->fuserRepository->findById($fuserId);
        $fuserDTO->userId = $userDTO->id;

        $this->fuserRepository->update($fuserDTO);

        $this->tokenService->setFuserId($fuserId);

        (new FavouritesService())->transferItems($fuserId, ['fuserId' => $fuserDTO->id, 'userId' => $fuserDTO->userId]);

        $tokenResult = $this->tokenService->generateNewTokens();

        $this->userRepository->resetLoginAttempts($userDTO->id);

        return [
            'user'         => $userDTO->except(...ProfileService::$excludedUserFields)->toArray(),
            'accessToken'  => $tokenResult['accessToken'],
            'refreshToken' => $tokenResult['refreshToken'],
            'message'      => Loc::getMessage('SUCCESSFUL_REGISTRATION'),
        ];
    }

    public function blockPhone(string $phoneNumber)
    {
        $agent    = sprintf('\NaturaSiberica\Api\Agents\User\UserAgent::unblockPhone(\'%s\');', $phoneNumber);
        $interval = sprintf('T%dS', Options::getBlockTimeout());

        $unblockDate = new DateTime();
        $unblockDate->add($interval);

        CAgent::AddAgent($agent, ModuleInterface::MODULE_ID, 'Y', 0, '', 'Y', $unblockDate->toString());
    }

    public function isPhoneBlocked(string $phoneNumber): bool
    {
        $agentName = sprintf('\NaturaSiberica\Api\Agents\User\UserAgent::unblockPhone(\'%s\');', $phoneNumber);
        $agent     = CAgent::GetList([], [
            'NAME' => $agentName,
        ])->Fetch();

        return ! empty($agent);
    }

    /**
     * @param int   $fuserId
     * @param array $body
     *
     * @return array
     * @throws Exception
     */
    public function login(int $fuserId, array $body): array
    {
        $this->tokenService->setFuserId($fuserId);

        UserValidator::validateEmptyPhone($body);
        UserValidator::validatePhone($body['phone']);

        $phone = UserPhoneAuthTable::normalizePhoneNumber($body['phone']);

        $userDTO = $this->userRepository->findByPhone($phone)->get();

        $this->userValidator->validateUnRegisteredUser($userDTO);
        $this->userValidator->validatePhoneInDTO($phone, $userDTO);
        $this->userValidator->validateLoginAttempts($userDTO->id);
        $this->userValidator->validateBlockedUser($userDTO->id);

        if (Options::isEnabledSendDataInMindbox()) {
            $mindboxCustomer   = $this->profileService->findCustomer($userDTO);
            $isMindboxCustomer = $mindboxCustomer['customer']['processingStatus'] === ProfileServiceInterface::RESPONSE_PROCESSING_STATUS_CUSTOMER_FOUND;

            if (! $isMindboxCustomer) {
                $registerCustomer   = $this->profileService->registerCustomer($userDTO);
                $userDTO->mindboxId = $registerCustomer['customer']['ids']['mindboxId'];
            } elseif ($isMindboxCustomer && empty($userDTO->mindboxId)) {
                $userDTO->mindboxId = $mindboxCustomer['customer']['ids']['mindboxId'];
                $this->userRepository->setMindboxId($userDTO->id, $userDTO->mindboxId);
            }

            $confirmPhoneInMindbox = $this->profileService->confirmPhone($userDTO);
            if ($confirmPhoneInMindbox['mobilePhoneConfirmation']['processingStatus'] === ProfileServiceInterface::RESPONSE_PROCESSING_STATUS_PHONE_CONFIRMED) {
                $userDTO->mindboxPhoneConfirmed = $this->userRepository->confirmPhoneInMindbox($userDTO->id);
            }
        }

        $code       = (string)$body['code'];
        $verifyCode = $this->phoneService->verifyCode($phone, $code);

        if (! $verifyCode) {
            $this->userRepository->addLoginAttempt($userDTO->id);

            throw new Exception(
                Loc::getMessage('main_err_confirm'), StatusCodeInterface::STATUS_BAD_REQUEST
            );
        }

        $this->tokenService->setUserId($userDTO->id);

        $linkedFuserId = $userDTO->fuserId;

        if ($linkedFuserId !== null && $fuserId !== $linkedFuserId) {
            CSaleBasket::TransferBasket($fuserId, $linkedFuserId);
            (new FavouritesService())->transferItems($fuserId, ['fuserId' => $linkedFuserId, 'userId' => $userDTO->id]);
            $fuserId = $linkedFuserId;
        }

        if ($userDTO->fuserId === null) {
            $userDTO->fuserId = $fuserId;
        }

        $this->tokenService->setFuserId($fuserId);

        $tokenResult = $this->tokenService->generateNewTokens();

        CSaleUser::Update($fuserId);

        $this->userRepository->resetLoginAttempts($userDTO->id);

        return [
            'user'         => $userDTO->except(...ProfileService::$excludedUserFields)->toArray(),
            'accessToken'  => $tokenResult['accessToken'],
            'refreshToken' => $tokenResult['refreshToken'],
            'message'      => Loc::getMessage('SUCCESSFUL_AUTHORIZATION'),
        ];
    }

    /**
     * @param int $userId
     * @param     $authHeader
     *
     * @return array[]
     * @throws Exception
     */
    public function logout(int $userId, $authHeader): array
    {
        $this->tokenService->setUserId($userId);

        $userDTO = $this->userRepository->findById($userId)->get();
        $this->tokenService->setFuserId($userDTO->fuserId);

        $accessToken = $this->tokenService->extractAccessToken($authHeader);
        $logout      = $this->tokenService->invalidateToken($accessToken);

        $this->userRepository->resetLoginAttempts($userId);

        return [
            'logout'  => $logout,
            'message' => Loc::getMessage('SUCCESSFUL_LOGOUT'),
        ];
    }

    public function blockUser(int $userId)
    {
        $blockUser   = $this->userRepository->blockUser($userId);
        $agent       = sprintf('\NaturaSiberica\Api\Agents\User\UserAgent::unblock(%d);', $userId);
        $interval    = sprintf('T%dS', Options::getBlockTimeout());
        $unblockDate = new DateTime();

        $unblockDate->add($interval);

        CAgent::AddAgent($agent, ModuleInterface::MODULE_ID, 'Y', 0, '', 'Y', $unblockDate->toString());

        return $blockUser;
    }

    protected function prepareRegistrationFields(string $phone): array
    {
        return [
            'LOGIN' => UserPhoneAuthTable::normalizePhoneNumber($phone),
            'PHONE' => UserPhoneAuthTable::normalizePhoneNumber($phone),
        ];
    }
}
