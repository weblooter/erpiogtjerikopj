<?php
ini_set('error_reporting', E_ERROR);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

@set_time_limit(0);
@ignore_user_abort(true);

$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__, 4);

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);
define("BX_CRONTAB", false);
define("BX_CRONTAB_SUPPORT", false);
define("CHK_EVENT", false);

require_once( $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php' );

use Bitrix\Main\Loader;
use NaturaSiberica\Api\Mindbox\MindboxService;

Loader::includeModule("iblock");
Loader::includeModule('demoapp.api');
Loader::includeModule('mindbox.marketing');

$service = new MindboxService();
$service->run();
