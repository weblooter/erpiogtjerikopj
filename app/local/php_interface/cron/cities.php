<?php

use Bitrix\Main\Loader;
use NaturaSiberica\Api\Services\Cron\DeliveryCityService;

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 3);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loader::includeModule('demoapp.api');

$service = new DeliveryCityService();
$service->run();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
