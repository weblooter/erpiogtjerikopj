<?php

namespace Userstory\ItsIntegrator;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Diag\Debug;
use Exception;
use JsonException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use NaturaSiberica\Api\Interfaces\Services\Token\TokenServiceInterface;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

class ItsConnector
{
    public const MODULE_ID = 'userstory.itsintegrator';
    /**
     * @var AMQPStreamConnection
     */
    private AMQPStreamConnection $connection;
    /**
     * @var AbstractChannel|AMQPChannel
     */
    private        $channel;
    private string $exchangeName;
    private string $queueName;

    public function __construct(string $exchangeName = 'thumbnailer_exchange', string $queueName = 'thumbnailer_channel')
    {
        $this->exchangeName = $exchangeName;
        $this->queueName    = $queueName;
        $this->connection   = $this->getConnection();
        $this->channel      = $this->prepareChannel($exchangeName, $queueName);
    }

    public function getConnection(): AMQPStreamConnection
    {
        try {
            $this->connection = new AMQPStreamConnection(
                Option::get(self::MODULE_ID, "HOST", ''),
                Option::get(self::MODULE_ID, "PORT", 5672),
                Option::get(self::MODULE_ID, "USER", ''),
                Option::get(self::MODULE_ID, "PASS", ''),
                Option::get(self::MODULE_ID, "VHOST", '/'),
            );
        } catch (Exception $exception) {
            self::log('connector_errors', Logger::ERROR, $exception->getMessage());
//            Debug::writeToFile($exception->getMessage(), __METHOD__, 'logs/its.integrator/thumbnailer_errors.log');
            die($exception->getMessage());
        }
        return $this->connection;
    }

    public function prepareChannel(string $exchangeName, string $queueName)
    {
        if (! empty($exchangeName)) {
            $this->exchangeName = $exchangeName;
        }
        if (! empty($queueName)) {
            $this->queueName = $queueName;
        }

        $channel = $this->connection->channel();
        $channel->queue_declare($this->queueName, false, true, false, false);
        $channel->exchange_declare($this->exchangeName, AMQPExchangeType::DIRECT, false, true, false);
        $channel->queue_bind($this->queueName, $this->exchangeName);

        return $channel;
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function queueMessage(array $message, string $iblockEvent = null): void
    {
        $messageBody  = json_encode($message, JSON_THROW_ON_ERROR);
        $messageReady = new AMQPMessage($messageBody, ['content_type' => 'text/json', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $this->channel->basic_publish($messageReady, $this->exchangeName);
        self::log('queue_' . ($iblockEvent ?? self::parseEvent($message['event'])), Logger::DEBUG, $message['event'], $message);
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

    public static function parseEvent(string $event)
    {
        if (preg_match('/thumbnailer.image.(upload|delete).complete/', $event, $matches)) {
            return $matches[1];
        }
    }

    public static function log(string $channel, int $level, string $message, array $data = [])
    {
        if ($data['timestamp']) {
            $data['timestamp'] = date(TokenServiceInterface::DEFAULT_DATETIME_FORMAT, strtotime($data['timestamp']));
        }
        if ($data['payload']['base64Image']) {
            unset($data['payload']['base64Image']);
        }

        $logger = new Logger($channel);
        $file = $_SERVER['DOCUMENT_ROOT'] . '/logs/' . self::MODULE_ID . '/' . $channel . '.log';
        $logger->pushHandler(new StreamHandler($file));
        $logger->addRecord($level, $message, $data);
    }
}
