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
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Config\Option;
use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\ORM\Query;

Loader::includeModule("iblock");

$timeStart = microtime(true);
echo 'Script is running;'.PHP_EOL;

function getIblockId(string $code): int
{
    $data = IblockTable::getList(['filter' => ['CODE' => $code], 'select' => ['ID']])->fetch();
    return ($data ? intval($data['ID']) : 0);
}

function getPropertyId(int $iblockId, string $code): int
{
    $data = PropertyTable::getList(['filter' => ['IBLOCK_ID' => $iblockId, 'CODE' => $code], 'select' => ['ID']])->fetch();
    return ($data ? intval($data['ID']) : 0);
}


function existImagesSku(int $offerIblockId, array $skuIdList): bool
{
    $entity = Iblock::wakeUp($offerIblockId)->getEntityDataClass();
    foreach ($skuIdList as $skuId) {
        $object = $entity::getList(['filter' => ['ID' => $skuId],'select' => ['ID', 'NAME', 'IMAGES.VALUE']])->fetchObject();
        if(!$object) {
            return false;
        }
        if(!$object->get('IMAGES') || !$object->get('IMAGES')->fill('VALUE')) {
            return false;
        }
    }

    return true;
}

function addSkuImages(int $offerIblockId, array $imagesList): array
{
    $skuIdList = [];
    $entity = Iblock::wakeUp($offerIblockId)->getEntityDataClass();
    foreach ($imagesList as $article => $images) {
        $object = $entity::getList([
            'filter' => ['ARTICLE.VALUE' => $article],
            'select' => ['ID', 'NAME', 'IMAGES']
        ])->fetchObject();
        if($object) {
            \CIBlockElement::SetPropertyValuesEx($object->get('ID'), $offerIblockId, ['IMAGES' => $images]);
            $skuIdList[] = $object->get('ID');
        }
    }

    return $skuIdList;
}

function getImages($item): array
{
    $result = [];
    $images = $item->get('IMAGES')->getAll();
    if($images) {
        foreach ($images as $image) {
            $file = $image->get('FILE');
            if($file) {
                $skuArticle = explode('_', $file->get('ORIGINAL_NAME'))[0];
                $result[$skuArticle][] = \CFile::makeFileArray($file->get('ID'));
            }
        }
    }

    return $result;
}


$offerIblockId = getIblockId('offers');
$productIblockId = getIblockId('products');



if(!getPropertyId($offerIblockId, 'IMAGES')) {
    echo 'Необходимо сначала установить миграцию IblockOffersPropertyImages20230529210220';
    die();
}

Option::set('main', 'control_file_duplicates', 'N');

$entity = Iblock::wakeUp($productIblockId)->getEntityDataClass();
$query = new Query($entity);


$stepCount = 20;
$currentStep = 0;
$step = (int)ceil($entity::getCount() / $stepCount);
while($step >= $currentStep) {

    $ids = $query->setSelect(['ID'])
                 ->setLimit($stepCount)
                 ->setOffset(($currentStep * $stepCount))
                 ->setOrder(['ID' => 'asc'])
                 ->fetchCollection()
                 ->fill('ID');

    $collection = $entity::getList([
        'filter' => ['ID' => $ids, '!IMAGES.VALUE' => false],
        'select' => ['ID', 'NAME', 'IMAGES.FILE']
    ])->fetchCollection();

    if($collection && $collection->count() > 0) {
        foreach ($collection as $item) {
            $images = getImages($item);
            if($images) {
                $skuIdList = addSkuImages($offerIblockId, $images);
                if(existImagesSku($offerIblockId, $skuIdList)) {
                    \CIBlockElement::SetPropertyValuesEx($item->get('ID'), $productIblockId, ['IMAGES' => ['VALUE' => '', 'DESCRIPTION' => '']]);
                }
            }
        }
    }

    $currentStep++;
}

Option::set('main', 'control_file_duplicates', 'Y');

echo 'Script running time: '.(microtime(true) - $timeStart).' second;'.PHP_EOL;
