<?php
if(php_sapi_name() !== 'cli') die('Not CLI');

@set_time_limit(0);
@ignore_user_abort(true);

$_SERVER["DOCUMENT_ROOT"] = dirname(__FILE__, 4);

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);
define("BX_CRONTAB", false);
define("BX_CRONTAB_SUPPORT", false);
define("CHK_EVENT", false);

require_once( $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php' );

\Bitrix\Main\Loader::includeModule('iblock');

$logFilePath = $_SERVER['DOCUMENT_ROOT'].'/local/logs/removeBadgeNew.log';
$maxSizeFile = 3145728;
if(file_exists($logFilePath) && filesize($logFilePath) > $maxSizeFile) {
    unlink($logFilePath);
}

$logger = new \Bitrix\Main\Diag\FileLogger($logFilePath);
$logger->setFormatter(new \NaturaSiberica\Api\Logger\LogFormatter());

$iblockData = \Bitrix\Catalog\CatalogIblockTable::getList(['filter' => ['PRODUCT_IBLOCK_ID' => false],'select' => ['IBLOCK_ID']])->fetchAll();
if(!$iblockData) {
    $logger->error('Инфоблоков с товарами не найдено.');
    exit('Возникла ошибка. Подробности: '.$logFilePath);
}

$catalogProductIdList = [];
foreach ($iblockData as $iblockItem) {
    $catalogProductIdList[] = $iblockItem['IBLOCK_ID'];
}
$logger->info('start: Начат поиск товаров созданых более 60 дней назад.');
$date = \Bitrix\Main\Type\DateTime::createFromPhp((new \DateTime()))->add('-60day')->toString();
foreach ($catalogProductIdList as $catalogProductId) {
    $dataClass = \Bitrix\Iblock\Iblock::wakeUp($catalogProductId)->getEntityDataClass();
    $elements = $dataClass::getList([
        'filter' => ['<DATE_CREATE' => $date,'!NEW.VALUE' => false],
        'order' => ['ID' => 'asc'],
        'select' => ['ID', 'NEW']
    ])->fetchCollection();
    if($elements && $elements->count() > 0) {
        foreach ($elements as $element) {
            $element->set('NEW', false);
            if($result = $element->save()->isSuccess()) {
                $logger->info('Товар id#'.$element->getId().' каталога id#'.$catalogProductId.' перестал быть "новинкой".');
            } else {
                $logger->error('Товар id#'.$element->getId().' каталога id#'.$catalogProductId.' не удалось обновить.');
            }
        }
    }
}
$logger->info('end: Поиск и обновление закончено.');
