<?php

namespace NaturaSiberica\Api\Services\Integration\Notification;

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Fig\Http\Message\StatusCodeInterface;
use Mindbox\DTO\DTO;
use Mindbox\Helper;
use NaturaSiberica\Api\DTO\NotificationDTO;
use NaturaSiberica\Api\Interfaces\NotificationInterface;
use NaturaSiberica\Api\Mindbox\MindboxRepository;

class PushService implements NotificationInterface
{
    protected MindboxRepository $repository;
    protected string $operation = 'MobilePush.SendCode';

    public function __construct()
    {
        $this->repository = new MindboxRepository();
    }

    public function sendCode(NotificationDTO $dto): Result
    {
        $result = new Result();
        $response = $this->sendRequest($dto->dispatch, $dto->message);
        if($response['status'] !== 'Success') {
            $result->addError(
                new Error('Уведомление не удалось отправить', StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY)
            );
        }
        $result->setData([
            'status' => 'OK',
            'code'   => $dto->message,
        ]);

        return $result;
    }

    public function sendRequest(string $deviceUUID, string $code): array
    {
        return $this->repository->getExport(
            $this->operation,
            [
                'mobilePushMailing' => [
                    'customParameters' => [
                        'Code' => $code
                    ]
                ]
            ],
            $deviceUUID
        );
    }
}
