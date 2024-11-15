<?php

namespace Userstory\ItsIntegrator;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\IO\File;
use CFile;
use DateTimeInterface;
use Exception;

class ItsProducer
{
    public const DEBUG_MODE = true;

    public function __construct()
    {
//        self::log(__METHOD__, __CLASS__, '__thumbnailer.log');
    }

    /**
     * @return bool
     */
    public static function isDebugMode(): bool
    {
        return self::DEBUG_MODE;
    }

    public static function log($var, $varName, $fileName = '__thumbnailer.log')
    {
        if (self::isDebugMode()) {
            Debug::writeToFile($var, $varName, $fileName);
        }
    }

    public static function getUuid(): string
    {
        $data = random_bytes(16);
        // Set version to 0100.
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10.
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function getImageGroupCodeByIblockId($iblockId = 1) {
//        self::log(__METHOD__, __CLASS__, '__thumbnailer.log');
        $imageGroups = self::getImageGroups();
        if (!empty($imageGroups[$iblockId])) {
            return $imageGroups[$iblockId];
        }
        return 'product';
    }

    public static function getImageGroups() {
//        self::log(__METHOD__, __CLASS__, '__thumbnailer.log');
        $val = Option::get('userstory.itsintegrator', "groupCodes");
        if($val !== '') {
            $arGroups = unserialize($val);
            foreach ($arGroups as $key => $val) {
                $groupsArray[$val['IBLOCK_ID']] = $val['GROUP_NAME'];
            }
        }
        return $groupsArray ?? [];
    }


    /**
     * Костыль для сохранения файла при обновлении элемента
     * @throws \Exception
     */
    public static function onFileSaveHandler($arFile, $fileName, $module): bool
    {
        return true;
    }


    /**
     * @throws \Exception
     */
    public static function handleAddEvent($arFields): bool
    {
        $catalogId = (int)Option::get(ItsConnector::MODULE_ID, "CATALOG_ID", 1);
        self::log(__METHOD__, __CLASS__, '__thumbnailer.log');
        try {
            if ($arFields["RESULT"] && (int)$arFields['IBLOCK_ID'] === $catalogId) {
                $exchange           = Option::get(ItsConnector::MODULE_ID, "EXCHANGE", 'thumbnailer_exchange');
                $queue              = Option::get(ItsConnector::MODULE_ID, "QUEUE", 'thumbnailer_queue');
                $thumbnailerService = new ItsConnector($exchange, $queue);

                $groupCode = self::getImageGroupCodeByIblockId($catalogId);
                $previewArray = (!empty($arFields['PREVIEW_PICTURE'])) ? $arFields['PREVIEW_PICTURE'] : $arFields['DETAIL_PICTURE'];

                if (! empty($previewArray)) {
                    $previewArray['PATH'] = $_SERVER['DOCUMENT_ROOT'] . $previewArray['SRC'];

                    $path      = $previewArray['PATH'];

                    if (file_exists($path)) {
                        $imageData = file_get_contents($path);
                        $base64    = base64_encode($imageData);

                        $message = [
                            "subject"   => "Userstory\\Microservices\\ItsIntegrator",
                            "event"     => "thumbnailer.image.upload.complete",
                            "version"   => "1.0.0",
                            'uuid'      => self::getUuid(),
                            "timestamp" => (new \DateTime())->format(DateTimeInterface::ATOM),
                            "payload"   => [
                                "imageName"   => $previewArray['FILE_NAME'],
                                "groupCode"   => $groupCode,
                                "base64Image" => $base64,
                            ]
                        ];
                        self::log($_REQUEST, '$_REQUEST', '__thumbnailer.log');
                        self::log($message, '$message', '__thumbnailer.log');
                        $thumbnailerService->queueMessage($message);
                    }
                }

            }
        } catch (Exception $exception) {
            self::log($exception->getMessage(), '$exception->getMessage()', '__thumbnailer_exceptions.log');
            self::log($exception->getTraceAsString(), '$exception->getTraceAsString()', '__thumbnailer_exceptions.log');
        }
        return true;
    }

    /**
     * @throws \Exception
     */
    public static function handleUpdateEvent($arFields): bool
    {
        self::log(__METHOD__, __CLASS__, '__thumbnailer.log');
        $catalogId = (int)Option::get(ItsConnector::MODULE_ID, "CATALOG_ID", 1);
        try {
            if ($arFields["RESULT"] && (int)$arFields['IBLOCK_ID'] === $catalogId) {
                $exchange           = Option::get(ItsConnector::MODULE_ID, "EXCHANGE", 'thumbnailer_exchange');
                $queue              = Option::get(ItsConnector::MODULE_ID, "QUEUE", 'thumbnailer_queue');
                $thumbnailerService = new ItsConnector($exchange, $queue);

                $groupCode = self::getImageGroupCodeByIblockId($catalogId);
                $previewArrayId = (!empty($arFields['PREVIEW_PICTURE_ID'])) ? $arFields['PREVIEW_PICTURE_ID'] : $arFields['DETAIL_PICTURE_ID'];
                if (! empty((int) $previewArrayId)) {
                    $previewArray = CFile::GetFileArray((int)$previewArrayId);
                    if (! empty($previewArray)) {
                        $previewArray['PATH'] = $_SERVER['DOCUMENT_ROOT'] . $previewArray['SRC'];
                        $path      = $previewArray['PATH'];
                        if (file_exists($path)) {
                            $imageData = file_get_contents($path);
                            $base64    = base64_encode($imageData);

                            $message = [
                                "subject"   => "Userstory\\Microservices\\ItsIntegrator",
                                "event"     => "thumbnailer.image.upload.complete",
                                "version"   => "1.0.0",
                                'uuid'      => self::getUuid(),
                                "timestamp" => (new \DateTime())->format(DateTimeInterface::ATOM),
                                "payload"   => [
                                    "imageName"   => $previewArray['FILE_NAME'],
                                    "groupCode"   => $groupCode,
                                    "base64Image" => $base64,
                                ]
                            ];
                            self::log($message, '$message', '__thumbnailer.log');
                            $thumbnailerService->queueMessage($message);
                        } else {
                            self::log('empty pic '.$previewArrayId, '', '__thumbnailer.log');
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            self::log($exception->getMessage(), '$exception->getMessage()', '__thumbnailer_exceptions.log');
            self::log($exception->getTraceAsString(), '$exception->getTraceAsString()', '__thumbnailer_exceptions.log');
        }

        return true;
    }

/**
     * @throws \Exception
     */
    public static function handleBeforeUpdateEvent($arFields): bool
    {
        self::log(__METHOD__, __CLASS__, '__thumbnailer.log');

        $catalogId = (int)Option::get(ItsConnector::MODULE_ID, "CATALOG_ID", 1);
        if ((int)$arFields['IBLOCK_ID'] === $catalogId) {
            /**
             * Удалим старый файл при обновлении на новый
             */
            if (! empty($arFields['PREVIEW_PICTURE']['old_file'])) {
                $fileIdToRemove = (int)$arFields['PREVIEW_PICTURE']['old_file'];

                self::shoutOutFileDeletion($fileIdToRemove, $catalogId);
            } elseif (! empty($arFields['DETAIL_PICTURE']['old_file'])) {
                $fileIdToRemove = (int)$arFields['DETAIL_PICTURE']['old_file'];

                self::shoutOutFileDeletion($fileIdToRemove, $catalogId);
            }
        }
        return true;
    }

    /**
     * @throws \Exception
     */
    public static function handleDeleteEvent($elementId): bool
    {
        self::log(__METHOD__, __CLASS__, '__thumbnailer.log');
        $catalogId = (int)Option::get(ItsConnector::MODULE_ID, "CATALOG_ID", 1);
        try {
            \Bitrix\Main\Loader::includeModule('iblock');
            $arSelect = ["ID", "IBLOCK_ID", "NAME", "DETAIL_PICTURE", "PREVIEW_PICTURE"];
            $arFilter = ["ID" => $elementId, "IBLOCK_ID" => $catalogId];
            $res = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
            if ($ob = $res->GetNextElement()){
                 $arFields = $ob->GetFields();
            }
            if (!empty($arFields)) {
                //self::log($arFields, __METHOD__, '__thumbnailer.log');
                if ((int)$arFields['IBLOCK_ID'] === $catalogId) {

                    $previewArrayId = (! empty($arFields['PREVIEW_PICTURE'])) ? $arFields['PREVIEW_PICTURE'] : $arFields['DETAIL_PICTURE'];
                    self::shoutOutFileDeletion($previewArrayId, $catalogId);
                }
            }
        } catch (Exception $exception) {
            self::log($exception->getMessage(), '$exception->getMessage()', '__thumbnailer_exceptions.log');
            self::log($exception->getTraceAsString(), '$exception->getTraceAsString()', '__thumbnailer_exceptions.log');
        }
        return true;
    }

    private static function shoutOutFileDeletion(int $imageFileId, int $catalogId) {
        self::log($imageFileId, __METHOD__ . ' $previewArrayId', '__thumbnailer.log');

        $exchange           = Option::get(ItsConnector::MODULE_ID, "EXCHANGE", 'thumbnailer_exchange');
        $queue              = Option::get(ItsConnector::MODULE_ID, "QUEUE", 'thumbnailer_queue');
        $thumbnailerService = new ItsConnector($exchange, $queue);
        $groupCode = self::getImageGroupCodeByIblockId($catalogId);
        if (! empty($imageFileId)) {
            $previewArray = CFile::GetFileArray($imageFileId);
            //self::log($previewArray, __METHOD__ . ' $previewArray', '__thumbnailer.log');
            if (! empty($previewArray)) {
                $previewArray['PATH'] = $_SERVER['DOCUMENT_ROOT'] . $previewArray['SRC'];
                $path      = $previewArray['PATH'];
                if (file_exists($path)) {
                    $imageData = file_get_contents($path);
                    $base64    = base64_encode($imageData);

                    $message = [
                        "subject"   => "Userstory\\Microservices\\ItsIntegrator",
                        "event"     => "thumbnailer.image.delete.complete",
                        "version"   => "1.0.0",
                        'uuid'      => self::getUuid(),
                        "timestamp" => (new \DateTime())->format(DateTimeInterface::ATOM),
                        "payload"   => [
                            "imageName"   => $previewArray['FILE_NAME'],
                            "groupCode"   => $groupCode,
                            "base64Image" => $base64,
                        ]
                    ];
                    //self::log($_REQUEST, '$_REQUEST', '__thumbnailer.log');
                    //self::log($message, '$message', '__thumbnailer.log');
                    $thumbnailerService->queueMessage($message);
                }
            }
        } else {
            self::log('empty pic', '', '__thumbnailer.log');
        }
    }

}
