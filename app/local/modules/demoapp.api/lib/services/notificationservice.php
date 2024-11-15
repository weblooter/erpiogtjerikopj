<?php

namespace NaturaSiberica\Api\Services;

use Bitrix\Main\Result;
use NaturaSiberica\Api\DTO\NotificationDTO;
//use NaturaSiberica\Api\Services\Integration\Notification\SmsService;
use NaturaSiberica\Api\Services\Integration\Notification\PushService;
use NaturaSiberica\Api\Tools\Settings\Options;

/**
 * Определяем канал отправки и делаем отправку сообщения
 */
class NotificationService
{
    private array $providerList = [
//        'sms' => SmsService::class,
        'push' => PushService::class
    ];
    private NotificationDTO $params;

    public function sendNotification(array $params): Result
    {
        $this->init($params);
        if(class_exists($this->params->provider)) {
            return (new $this->params->provider())->sendCode($this->params);
        }

        return new Result();
    }

    public function init(array $params)
    {
//        if(mb_stripos($params['userAgent'], 'dart') !== false) {
            $this->params = new NotificationDTO([
                'provider' => $this->providerList['push'],
                'dispatch' => $params['uuid'],
                'message' => '1234',//$params['code']
            ]);
//        } else {
//            $this->params = new NotificationDTO([
//                'provider' => $this->providerList['sms'],
//                'dispatch' => $params['phone'],
//                'message' => $params['code']
//            ]);
//        }
    }

//    public function checkNotificationCodeSendAttempts(object $row): bool
//    {
//        return $row->get('SMS_CODE_SEND_ATTEMPTS') < Options::getSmsCodeSendAttempts();
//    }
//
//    public function checkBlockTimeoutEnd(object $row): bool
//    {
//        return $this->getBlockTimeoutEnd($row) < time();
//    }
//
//    public function getBlockTimeoutEnd(object $row)
//    {
//        if (empty($row->getDateSent()) || $row->get('SMS_CODE_SEND_ATTEMPTS') < Options::getSmsCodeSendAttempts() || $row->get('REGISTER_ATTEMPTS') < Options::getLoginAttempts()) {
//            return;
//        }
//
//        return $row->getDateSent()->getTimestamp() + Options::getBlockTimeout();
//    }
//
//    public function checkResendInterval(object $row)
//    {
//        if ($row->getDateSent()) {
//            $interval = time() - $row->getDateSent()->getTimestamp();
//
//            return $interval > Options::getSmsCodeResendInterval();
//        }
//
//        return true;
//    }
}
