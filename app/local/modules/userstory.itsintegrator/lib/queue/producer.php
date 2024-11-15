<?php

namespace Userstory\ItsIntegrator\Queue;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Diag\FileLogger;
use Bitrix\Main\IO\FileNotFoundException;
use DateTime;
use Monolog\Logger;
use NaturaSiberica\Api\Logger\LogFormatter;
use Psr\Log\LoggerInterface;
use Userstory\ItsIntegrator\Event\IblockHandler;
use Userstory\ItsIntegrator\ItsConnector;
use Userstory\ItsIntegrator\ItsProducer;

class Producer
{

    private ItsConnector $connector;

    /**
     * @param ItsConnector $connector
     */
    public function __construct(ItsConnector $connector)
    {
        $this->connector = $connector;
    }

    /**
     * @param string $groupCode
     * @param string $event
     * @param array  $file
     *
     * @return array|bool
     *
     */
    public function prepareMessage(string $groupCode, string $event, array $file)
    {
        $path     = $file['PATH'];
        $dateTime = new DateTime();

        if (! $file['IS_EXISTS']) {
            $varName = sprintf('%s event error: file %s not exists', ucfirst($event), $file['PATH']);

            ItsConnector::log($event . '_error', Logger::ERROR, $varName, [
                'ts'        => $dateTime->format(DateTime::RSS),
                'groupCode' => $groupCode,
                'event'     => $event,
                'file'      => $file,
            ]);

            return false;
        }

        $imageData = file_get_contents($path);
        $base64    = base64_encode($imageData);

        return [
            'subject'   => Option::get(ItsConnector::MODULE_ID, 'message_subject'),
            'event'     => $this->getEvent($event),
            'version'   => Option::get(ItsConnector::MODULE_ID, 'version'),
            'uuid'      => ItsProducer::getUuid(),
            'timestamp' => $dateTime->format(DateTime::ATOM),
            'payload'   => [
                'imageName'   => $file['FILE_NAME'],
                'groupCode'   => $groupCode,
                'base64Image' => $base64,
            ],
        ];
    }

    private function getEvent(string $event): string
    {
        return sprintf('thumbnailer.image.%s.complete', $event);
    }

    public function send(array $message, string $iblockEvent = null): void
    {
        $this->connector->queueMessage($message, $iblockEvent);
    }
}
