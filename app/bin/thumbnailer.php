<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use NaturaSiberica\Api\Repositories\Catalog\OffersRepository;
use Userstory\ItsIntegrator\Event\IblockHandler;
use Userstory\ItsIntegrator\ItsConnector;
use Userstory\ItsIntegrator\ItsProducer;
use Userstory\ItsIntegrator\Queue\Producer;

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);

const NO_KEEP_STATISTIC     = true;
const NOT_CHECK_PERMISSIONS = true;
const NO_AGENT_CHECK        = true;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

Loader::includeModule('demoapp.api');
Loader::includeModule('userstory.itsintegrator');

$exchange = Option::get(ItsConnector::MODULE_ID, "EXCHANGE", 'thumbnailer_exchange');
$queue    = Option::get(ItsConnector::MODULE_ID, "QUEUE", 'thumbnailer_queue');

$connector  = new ItsConnector($exchange, $queue);
$producer   = new Producer($connector);
$repository = new OffersRepository('offers');

$iblockId  = $repository->getIblockId('offers');
$groupCode = ItsProducer::getImageGroupCodeByIblockId($iblockId);

$collection = $repository->getElementCollection($iblockId);

$previewPictureIds = $collection->fill('PREVIEW_PICTURE');
$detailPictureIds  = $collection->fill('DETAIL_PICTURE');
$imagesIds         = $collection->fill('IMAGES')->fill('VALUE');

$images = $repository->getImageData(
    array_unique(
        array_merge(
            $previewPictureIds,
            $detailPictureIds,
            $imagesIds
        )
    )
);

foreach ($images as $image) {
    if (! $image['IS_EXISTS']) {
        continue;
    }
    $message = $producer->prepareMessage($groupCode, IblockHandler::EVENT_UPLOAD, $image);
    $producer->send($message);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
