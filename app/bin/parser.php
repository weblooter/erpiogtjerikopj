<?php

use Bitrix\Main\Loader;
use NaturaSiberica\Api\Tools\Parsers\ProductsParser;

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loader::includeModule('demoapp.api');
$csvPath = dirname(__DIR__) . '/xls/products.csv';

$parser = new ProductsParser();
$parser->parseCsv($csvPath)->import();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
