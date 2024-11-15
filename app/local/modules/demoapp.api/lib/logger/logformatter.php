<?php

namespace NaturaSiberica\Api\Logger;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Diag\LogFormatterInterface;

class LogFormatter implements LogFormatterInterface
{
    public function format($message, array $context = []): string
    {
        $date = date('d-m-Y H:i:s');
        $host = $_SERVER['HTTP_HOST'] ?? Option::get('main', 'server_name');
        return sprintf('%s - %s: %s' . PHP_EOL, $date, $host, $message);
    }
}
