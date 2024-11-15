<?php
$_SERVER["DOCUMENT_ROOT"] = dirname(__FILE__, 4);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use NaturaSiberica\Api\Mindbox\YmlFeedMindboxCustom;

$_SERVER["SERVER_NAME"] = trim(Option::get('main', 'server_name'), '/');

try {
    if (Loader::includeModule('mindbox.marketing')) {
        YmlFeedMindboxCustom::start();
    }
} catch (LoaderException $e) {
    die();
}
