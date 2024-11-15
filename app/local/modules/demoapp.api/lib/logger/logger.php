<?php

namespace NaturaSiberica\Api\Logger;

use Bitrix\Main\Diag\FileLogger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger implements LoggerInterface
{
    private static array $instance = [];
    private LoggerInterface $logger;
    private array $logData = [];

    private function __construct(string $channel)
    {
        switch ($channel) {
            case 'files':
                $this->logger = new FileLogger($_SERVER['DOCUMENT_ROOT'].'/local/logs/log.log');
                $this->logger->setFormatter((new LogFormatter()));
                break;
            default:
                $this->logger = new \Monolog\Logger($channel);
                $this->logger->pushHandler(new StreamHandler($_SERVER['DOCUMENT_ROOT'] . '/local/logs/'.$channel.'.log'));
                break;
        }

    }

    public static function getInstance(string $channel)
    {
        if(!array_key_exists($channel, self::$instance)) {
            self::$instance[$channel] = new self($channel);
        }
        return self::$instance[$channel];
    }

    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        $this->logger->log($level, $message, array_merge($this->logData, $context));
    }
}
