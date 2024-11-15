<?php

use Bitrix\Main\Loader;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

Loader::includeModule('sale');
Loader::includeModule('demoapp.api');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
