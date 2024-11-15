<?php

namespace NaturaSiberica\Api\Services\User;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserPhoneAuthTable;
use CUser;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Mindbox\Clients\AbstractMindboxClient;
use Mindbox\Core;
use Mindbox\DTO\V3\Requests\CustomerRequestDTO;
use Mindbox\Exceptions;
use Mindbox\Mindbox;
use NaturaSiberica\Api\DTO\User\UserDTO;
use NaturaSiberica\Api\Entities\User\PhoneAuthTable;
use NaturaSiberica\Api\Interfaces\Services\Mindbox\MindboxServiceInterface;
use NaturaSiberica\Api\Interfaces\Services\User\PhoneServiceInterface;
use NaturaSiberica\Api\Logger\EventLogRecorder;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Security\TotpAlgorithm;
use NaturaSiberica\Api\Services\Integration\SmsService;
use NaturaSiberica\Api\Services\Mindbox\MindboxService;
use NaturaSiberica\Api\Tools\Settings\Options;
use NaturaSiberica\Api\Validators\ResultValidator;
use NaturaSiberica\Api\Validators\User\UserValidator;

Loader::includeModule('mindbox.marketing');

Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/user.php');
Loc::loadMessages(__DIR__ . '/authservice.php');

class PhoneService implements PhoneServiceInterface
{
    private MindboxServiceInterface $mindboxService;

    private Mindbox               $mindbox;
    private AbstractMindboxClient $mindboxClient;
    private SmsService            $smsService;
    private TotpAlgorithm         $totp;
    private UserRepository        $userRepository;
    private UserValidator         $userValidator;
    private ResultValidator       $resultValidator;
    private CUser                 $user;

    public function __construct(UserRepository $userRepository)
    {
        $this->mindboxService  = new MindboxService();
        $this->smsService      = new SmsService();
        $this->userRepository  = $userRepository;
        $this->userValidator   = new UserValidator($this->userRepository);
        $this->resultValidator = new ResultValidator();
        $this->user            = new CUser();
        $this->mindbox         = $this->mindboxService->getMindbox();
        $this->mindboxClient   = $this->mindboxService->getMindboxClient();

        $this->setTotp();
    }

    public function getCaptchaKey()
    {
        return ['siteKey' => Options::getReCaptchaSiteKey()];
    }

    private function setTotp()
    {
        $digits     = Options::getSmsCodeDigits();
        $this->totp = new TotpAlgorithm();
        $this->totp->setDigits($digits);
    }

    /**
     * @return UserRepository
     */
    public function getUserRepository(): UserRepository
    {
        return $this->userRepository;
    }

    /**
     * @return CUser
     */
    public function getUser(): CUser
    {
        return $this->user;
    }

    /**
     * @return SmsService
     */
    public function getSmsService(): SmsService
    {
        return $this->smsService;
    }

    /**
     * @param array    $body
     * @param int      $fuserId
     * @param int|null $userId
     *
     * @return array
     *
     * @throws Exception
     */
    public function generateCode(array $body, int $fuserId, int $userId = null): array
    {
        if ($userId) {
            $this->userValidator->validateBlockedUser($userId);
        }

        $authService = new AuthService();
        $this->userValidator->setAuthService($authService);

        $phone = $body['phone'];

        $this->userValidator->validatePhone($phone);
        $this->userValidator->validateBlockedPhone($phone);

        if (Options::isEnabledSendDataInMindbox()) {
            $this->userValidator->validateMindboxCustomerByPhone($phone);
        }

        $row = PhoneAuthTable::getByPrimary($phone)->fetchObject();

        if (! $row) {
            [$code, $phoneNumber] = $this->createCode($phone);

            $result = $this->smsService->sendCode($phoneNumber, $code);
            $this->resultValidator->validate($result);

            return [];
        }

        $checkAttempts        = $this->smsService->checkSmsCodeSendAttempts($row);
        $checkBlockTimeoutEnd = $this->smsService->checkBlockTimeoutEnd($row);
        $checkResendInterval  = $this->smsService->checkResendInterval($row);

        if (! $checkAttempts) {
            $authService->blockPhone($phone);
            throw new Exception(Loc::getMessage('error_blocked_phone'), StatusCodeInterface::STATUS_FORBIDDEN);
        } elseif (! $checkBlockTimeoutEnd) {
            throw new Exception(Loc::getMessage('error_blocked_phone_timeout'), StatusCodeInterface::STATUS_FORBIDDEN);
        } elseif (! $checkResendInterval) {
            throw new Exception(Loc::getMessage('main_register_timeout'), StatusCodeInterface::STATUS_FORBIDDEN);
        }

        [$code, $phoneNumber] = $this->createCode($phone);

        $result = $this->smsService->sendCode($phoneNumber, $code);
        $this->resultValidator->validate($result);

        return [];
    }

    /**
     * @param string $phoneNumber
     *
     * @return array|false
     *
     * @throws Exception
     */
    public function createCode(string $phoneNumber)
    {
        $this->totp->setInterval(CUser::PHONE_CODE_OTP_INTERVAL);

        $row = PhoneAuthTable::getByPrimary($phoneNumber)->fetchObject();

        if (! $row) {
            $addResult = PhoneAuthTable::add([
                'PHONE_NUMBER' => $phoneNumber,
                'DATE_SENT'    => new DateTime()
            ]);

            $row = PhoneAuthTable::getByPrimary($addResult->getId())->fetchObject();
        }

        if ($row->get('OTP_SECRET') <> '') {
            $this->totp->setSecret($row->get('OTP_SECRET'));
            // TODO: start: Убрать, когда не нужен будет тестовый режим
            if(Options::getTestingMode() === 'Y' && $phoneNumber === Options::getTestingTelephoneNumber()) {
                $code = Options::getTestingConfirCode();
                // TODO: end: Убрать, когда не нужен будет тестовый режим
            } elseif (Options::getSmsGatewayApiGenerateNeed() === 'Y') {
                $timecode = $this->totp->timecode(time());
                $code     = $this->totp->generateOTP($timecode);
            } else {
                $code = Options::getSmsGatewayApiStaticCode();
            }

            PhoneAuthTable::update($phoneNumber, [
                'SMS_CODE_SEND_ATTEMPTS' => $row->get('SMS_CODE_SEND_ATTEMPTS') + 1,
                'DATE_SENT'              => new DateTime(),
            ]);

            return [$code, $row->get('PHONE_NUMBER')];
        }

        return false;
    }

    /**
     * @param array    $body
     * @param int      $fuserId
     * @param int|null $userId
     *
     * @return bool[]
     *
     * @throws Exception
     */
    public function confirm(array $body, int $fuserId, int $userId = null): array
    {
        UserValidator::validatePhone($body['phone']);

        return [
            'confirmed' => true,
        ];
    }

    public function verifyCode(string $phoneNumber, string $code, string $field = null): bool
    {
        if ($code == '') {
            return false;
        }

        switch ($field) {
            case 'SMS_CODE_SEND_ATTEMPTS':
                $maxAttempts = Options::getSmsCodeSendAttempts();
                break;
            case 'REGISTER_ATTEMPTS':
                $maxAttempts = Options::getLoginAttempts();
                break;
        }

        $phoneNumber = UserPhoneAuthTable::normalizePhoneNumber($phoneNumber);

        $row = PhoneAuthTable::getList(["filter" => ["=PHONE_NUMBER" => $phoneNumber]])->fetchObject();

        if (! $row) {
            throw new Exception(Loc::getMessage('error_sms_code_not_sent'), StatusCodeInterface::STATUS_FAILED_DEPENDENCY);
        }

        if ($row && $row->get('OTP_SECRET') <> '') {
            if ($field && $row->get($field) > $maxAttempts) {
                return false;
            }

            $this->totp->setInterval(CUser::PHONE_CODE_OTP_INTERVAL);
            $this->totp->setSecret($row->get('OTP_SECRET'));

            try {
                // TODO: start: Убрать, когда не нужен будет тестовый режим
                if(Options::getTestingMode() === 'Y' && $phoneNumber === Options::getTestingTelephoneNumber()) {
                    $result = $code === Options::getTestingConfirCode();
                    // TODO: end: Убрать, когда не нужен будет тестовый режим
                } elseif (Options::getSmsGatewayApiGenerateNeed() === 'Y') {
                    [$result,] = $this->totp->verify($code);
                } else {
                    $result = $code === Options::getSmsGatewayApiStaticCode();
                }
            } catch (ArgumentException $e) {
                EventLogRecorder::addErrorLog($e);
                return false;
            }

            $data = [];
            if ($result) {
                $data['DATE_SENT'] = new DateTime();
            } elseif ($field) {
                $data[$field] = $row->get($field) + 1;
            }

            if (! empty($data)) {
                PhoneAuthTable::update($row->get('PHONE_NUMBER'), $data);
            }

            return $result;
        }
        return false;
    }

    private function prepareMindboxRequestBody(int $userId): array
    {
        $userDto = $this->userRepository->findById($userId)->get();

        if ($userDto->mindboxId === null) {
            throw new Exception(Loc::getMessage('error_mindbox_user_not_found'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        return [
            'customer' => [
                'ids'         => [
                    'mindboxId' => $userDto->mindboxId,
                ],
                'mobilePhone' => $userDto->phone,
            ],

        ];
    }

    public function sendNotification(array $data)
    {
        if(!$data['userAgent'] || !$data['uuid'] || !$data['phone']) {
            throw new Exception('Не верные данные.', 400);
        }
        $result = (new \NaturaSiberica\Api\Services\NotificationService())->sendNotification($data);
        $this->resultValidator->validate($result);

        return $result->getData();
    }
}
