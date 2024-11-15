<?php

namespace NaturaSiberica\Api\Services\Integration;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\Sms\Event as SmsEvent;
use Bitrix\Main\Sms\Message;
use Bitrix\Main\Sms\TemplateTable;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\Web\HttpClient;
use CUser;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Entities\User\PhoneAuthTable;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Security\TotpAlgorithm;
use NaturaSiberica\Api\Tools\Settings\Options;

Loc::loadMessages(__FILE__);
Loc::loadMessages(dirname(__DIR__) . '/user/authservice.php');

class SmsService
{
    const         PHONE_CODE                    = 1234;
    private const SMS_USER_CONFIRM_NUMBER_EVENT = 'SMS_USER_CONFIRM_NUMBER';

    private const API_BASE_URL = 'https://api.mts.ru/client-omni-adapter_production/1.0.2/mcom';

    private array $fields = [];

    private ?SmsEvent   $smsEvent = null;
    private HttpClient  $client;
    private static bool $isSend   = false;

    public function __construct()
    {
        self::$isSend = (Options::getSmsGatewayApiNeed() === 'Y');
        if (self::$isSend) {
            $this->setClient();
        }
    }

    private function setClient()
    {
        $this->client = new HttpClient();
        $this->client->setHeader('Authorization', 'Bearer ' . Options::getSmsGatewayApiToken());
        $this->client->setHeader('Content-Type', 'application/json');
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function initSmsEvent(string $phone, int $code): SmsService
    {
        $template = $this->getTemplate(self::SMS_USER_CONFIRM_NUMBER_EVENT, true);

        $this->fields['USER_PHONE'] = $phone;
        $this->fields['CODE']       = $code;
        $this->smsEvent             = new SmsEvent(self::SMS_USER_CONFIRM_NUMBER_EVENT, $this->fields);

        $this->smsEvent->setSite(Context::getCurrent()->getSite());
        $this->smsEvent->setLanguage(LANGUAGE_ID);
        $this->smsEvent->setTemplate($template['ID']);

        return $this;
    }

    /**
     * @param string      $endpoint
     * @param string|null $body
     *
     * @return array|null
     */
    private function sendRequest(string $endpoint, string $body = null): ?array
    {
        $url     = $this->getUrl($endpoint);
        $request = $this->client->post($url, $body);

        return $request ? json_decode($request, true) : ['error' => json_last_error_msg()];
    }

    /**
     * Высылает одноразовый код подтверждения на переданный номер телефона
     *
     * @param string $phone
     * @param string $code
     *
     * @return Result
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function sendCode(string $phone, string $code): Result
    {
        $result = new Result();

        if (self::$isSend
            // TODO: start: Убрать, когда не нужен будет тестовый режим
            && (Options::getTestingMode() !== 'Y' || $phone !== Options::getTestingTelephoneNumber())
            // TODO: end: Убрать, когда не нужен будет тестовый режим
        ) {
            if ($this->smsEvent === null) {
                $this->initSmsEvent($phone, $code);
            }

            $response = $this->sendRequest('messageManagement/messages', $this->prepareBody());

            if (! empty($response['fault'])) {
                $result->addError(
                    new Error($response['fault']['description'], StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY)
                );
            }
        }

        $result->setData([
            'status' => 'OK',
            'code'   => $code,
        ]);

        return $result;
    }

    /**
     * @param string $endpoint
     *
     * @return string
     */
    private function getUrl(string $endpoint): string
    {
        return self::API_BASE_URL . '/' . $endpoint;
    }

    /**
     * @param bool $onlyText
     *
     * @return Message|string|void
     */
    private function getMessage(bool $onlyText = false)
    {
        $messages = $this->smsEvent->createMessageList()->getData();

        /**
         * @var Message $message
         */
        foreach ($messages as $message) {
            return $onlyText ? $message->getText() : $message;
        }
    }

    /**
     * @return string
     */
    public function prepareBody(): string
    {
        $phone = ltrim($this->fields['USER_PHONE'], '+');
        $body  = [
            'submits' => [
                [
                    'msid'    => $phone,
                    'message' => $this->getMessage(true),
                ],
            ],
            'naming'  => Options::getSmsGatewaySenderName(),
        ];

        return json_encode($body, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $eventName
     * @param bool   $onlyId
     *
     * @return array|int
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getTemplate(string $eventName, bool $onlyId = false)
    {
        $template = TemplateTable::getList([
            'filter' => ['EVENT_NAME' => $eventName],
        ])->fetch();

        if (! $template) {
            return $onlyId ? 0 : [];
        }

        return $onlyId ? (int)$template['ID'] : $template;
    }

    /**
     * @param object $row
     *
     * @return int|void
     */
    public function getBlockTimeoutEnd(object $row)
    {
        if (empty($row->getDateSent()) || $row->get('SMS_CODE_SEND_ATTEMPTS') < Options::getSmsCodeSendAttempts() || $row->get('REGISTER_ATTEMPTS') < Options::getLoginAttempts()) {
            return;
        }

        return $row->getDateSent()->getTimestamp() + Options::getBlockTimeout();
    }

    public function checkSmsCodeSendAttempts(object $row): bool
    {
        return $row->get('SMS_CODE_SEND_ATTEMPTS') < Options::getSmsCodeSendAttempts();
    }

    public function checkBlockTimeoutEnd(object $row): bool
    {
        return $this->getBlockTimeoutEnd($row) < time();
    }

    public function checkResendInterval(object $row): bool
    {
        if ($row->getDateSent()) {
            $interval = time() - $row->getDateSent()->getTimestamp();

            return $interval > Options::getSmsCodeResendInterval();
        }

        return true;
    }
}
