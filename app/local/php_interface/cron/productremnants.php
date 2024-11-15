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

$logger = new \Bitrix\Main\Diag\FileLogger($_SERVER['DOCUMENT_ROOT'].'/local/logs/productremnants.log');
$logger->setFormatter(new \NaturaSiberica\Api\Logger\LogFormatter());
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');
$iblock = \Bitrix\Iblock\IblockTable::getList([
        'filter' => ['CODE' => 'offers'],
        'select' => ['ID']
    ])->fetchObject();

if(!$iblock) {
    $logger->error('Не найден инфоблок');
    die('Not iblock offers');
}
$storeId = \NaturaSiberica\Api\Tools\Settings\Options::getDefaultStore();
$elements = \Bitrix\Iblock\Elements\ElementOffersTable::getList([
        'filter' => [
            'ACTIVE' => 'Y',
            '>STORE.AMOUNT' => 0
        ],
        'order' => ['ID' => 'asc'],
        'select' => ['ID', 'STORE.ID', 'MLK_ID_' => 'MLK_ID'],
        'runtime' => [
            new \Bitrix\Main\Entity\ReferenceField(
                'STORE',
                \Bitrix\Catalog\StoreProductTable::class,
                Bitrix\Main\Entity\Query\Join::on('this.ID', 'ref.PRODUCT_ID')->where('ref.STORE_ID', $storeId)
            )
        ]
    ])->fetchCollection();

if($elements && $elements->count() > 0) {
    foreach ($elements as $element) {
        if (empty($element->get('MLK_ID')->getValue())) {
            $result = \Bitrix\Catalog\StoreProductTable::update($element->get('STORE')->get('ID'),['AMOUNT' => 0]);
            if($result->isSuccess()) {
                $logger->info('Количество товара id #'.$element->get('ID').' на складе id #'.$storeId.' обнулено.');
            } else {
                $logger->error('Ошибка обновления товара id #'.$element->get('ID'));
                foreach ($result->getErrors() as $error) {
                    $logger->error($error);
                }
            }
        }
    }
} else {
    $logger->info('Ни одного подходящего элемента не найдено.');
}
