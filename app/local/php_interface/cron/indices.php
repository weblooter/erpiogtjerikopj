<?php

/**
 * @var array $argv Переменная с аргументами скрипта
 */

if(php_sapi_name() !== 'cli') die('Not CLI');

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
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\SiteTable;
use NaturaSiberica\Api\ElasticSearch\ElasticSearchService;
use NaturaSiberica\Api\ElasticSearch\Indices\ProductIndices;

Loader::includeModule('demoapp.api');
Loader::includeModule("iblock");

$indexList = [];
$catalogs = IblockTable::getList([
    'filter' => ['IBLOCK_TYPE_ID' => 'catalog'],
    'select' => ['CODE', 'LID']
])->fetchAll();

foreach ($catalogs as $catalog) {
    $indexList[$catalog['LID']][] = $catalog['CODE'];
}

$result = [];
$propertyIndexName = 'properties';
$service = new ElasticSearchService();
foreach ($indexList as $lid => $indices) {
    foreach ($indices as $index) {
        $result[$lid][] =  ($argv[1] === 'delete'
            ? $service->deleteIndex($index)
            : $service->createIndex($index)
        );
    }
    $result[$lid][] =  ($argv[1] === 'delete'
        ? $service->deleteIndex(($lid !== 's1' ? $propertyIndexName.'_'.$lid : $propertyIndexName))
        : $service->createIndex(($lid !== 's1' ? $propertyIndexName.'_'.$lid : $propertyIndexName))
    );
}


foreach ($indexList as $lid => $indices) {
    $res = [];
    for ($i = 0; $i < count($indices); $i++) {
        $list = explode('_', $indices[$i]);
        $className = ucfirst($list[0]).'Indices';
        $class = '\NaturaSiberica\Api\ElasticSearch\Indices\\'.$className;
        if(class_exists($class)) {
            $res[] = (new $class($indices[$i]))->run($list[0]);
        }
        $propertyClass = '\NaturaSiberica\Api\ElasticSearch\Indices\PropertiesIndices';
        if(class_exists($propertyClass)) {
            $res[] = (new $propertyClass(($lid !== 's1' ? $propertyIndexName.'_'.$lid : $propertyIndexName)))->run($indices[$i]);
        }
    }

}
//dump($res);
