<?php

namespace Userstory\ItsIntegrator\Event;

use Bitrix\Iblock\Iblock;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\Model\Section;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use CIBlockElement;
use NaturaSiberica\Api\Entities\Iblock\ElementCatalogMultiPropertyTable;
use NaturaSiberica\Api\Helpers\Http\UrlHelper;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;
use Naturasiberica\Productimport\Logger;
use Userstory\ItsIntegrator\ItsConnector;
use Userstory\ItsIntegrator\Queue\Producer;

class IblockHandler
{
    use InfoBlockTrait, FileTrait;

    const EVENT_UPLOAD = 'upload';
    const EVENT_DELETE = 'delete';
    public array         $defaultElementImageFields = ['PREVIEW_PICTURE', 'DETAIL_PICTURE'];
    public array         $defaultSectionImageFields = ['PICTURE', 'DETAIL_PICTURE'];
    private ItsConnector $connector;
    private Producer     $producer;
    private ?Iblock      $iblock                    = null;
    /**
     * @var SectionTable|string|null
     */
    private $section = null;

    public function __construct()
    {
        $this->initConnector();

        $this->producer = new Producer($this->connector);
    }

    private function initConnector()
    {
        $exchange        = Option::get(ItsConnector::MODULE_ID, "EXCHANGE", 'thumbnailer_exchange');
        $queue           = Option::get(ItsConnector::MODULE_ID, "QUEUE", 'thumbnailer_queue');
        $this->connector = new ItsConnector($exchange, $queue);
    }

    public function getIblockIdBySectionId(int $sectionId)
    {
        $section = SectionTable::getByPrimary($sectionId, [
            'select' => ['IBLOCK_ID'],
        ])->fetchObject();

        return ! empty($section) ? $section->getIblockId() : false;
    }

    public function getIblockIdByElementId(int $elementId)
    {
        return CIBlockElement::GetIBlockByID($elementId);
    }

    /**
     * @return Iblock|null
     */
    public function getIblock(): ?Iblock
    {
        return $this->iblock;
    }

    /**
     * @param array $options
     *
     * @return void
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function OnAfterIBlockElementAddHandler(array $options): void
    {
        $iblockId        = $options['iblock_id'];
        $groupCode       = $options['group_code'];
        $imageProperties = $this->getIblockImageProperties($iblockId);
        $propertyList    = $this->getIblockPropertyList($iblockId, ['CODE' => $imageProperties]);

        $imgIds = [];

        foreach ($this->defaultElementImageFields as $imageField) {
            $key = $imageField . '_ID';
            if (! empty($options['fields'][$key])) {
                $imgIds[] = $options['fields'][$key];
            }
        }

        foreach ($propertyList as $property) {
            $pId            = (int)$property['ID'];
            $pCode          = $property['CODE'];
            $propertyValues = $options['fields']['PROPERTY_VALUES'][$pId] ?? $options['fields']['PROPERTY_VALUES'][$pCode];
            $imageNames     = [];

            foreach ($propertyValues as $propertyValue) {
                $imageNames[] = $propertyValue['name'];
            }
        }

        if (! empty($imageNames)) {
            $images = $this->prepareImages([
                'filter' => [
                    'LOGIC' => 'OR',
                    [
                        'ID' => $imgIds,
                    ],
                    [
                        'ORIGINAL_NAME' => $imageNames,
                    ],
                ],
            ]);

            if (! empty($images)) {
                foreach ($images as $image) {
                    $message = $this->producer->prepareMessage($groupCode, self::EVENT_UPLOAD, $image);

                    if (! $message) {
                        continue;
                    }

                    $this->producer->send($message, $options['event']);
                }
            }
        }
    }

    public function getIblockImageProperties(int $iblockId)
    {
        $code            = $this->getIblockCodeById($iblockId);
        $imageProperties = $this->getImageProperties();

        return $imageProperties[$code];
    }

    public function getImageProperties(): array
    {
        $imageProperties = Option::get(ItsConnector::MODULE_ID, 'image_properties');
        return unserialize($imageProperties);
    }

    public function prepareImages(array $params = []): array
    {
        if (empty($params['select'])) {
            $params['select'] = ['ID', 'FILE_NAME', 'SUBDIR'];
        }

        $result    = [];
        $imageData = $this->getRawData($params)->fetchAll();

        foreach ($imageData as $item) {
            $item['PATH']      = $_SERVER['DOCUMENT_ROOT'] . UrlHelper::getFileUrnFromArray($item);
            $item['IS_EXISTS'] = file_exists($item['PATH']);

            if (empty(array_column($result, 'PATH')) || ! in_array($item['PATH'], array_column($result, 'PATH'))) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param array $options
     *
     * @return void
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function OnBeforeIBlockElementUpdateHandler(array $options): void
    {
        $iblockId        = $options['iblock_id'];
        $groupCode       = $options['group_code'];
        $imageProperties = $this->getIblockImageProperties($iblockId);
        $propertyList    = $this->getIblockPropertyList($iblockId, ['CODE' => $imageProperties]);
        $deleteValues    = [];
        $logData         = [];

        foreach ($this->defaultElementImageFields as $field) {
            if ($options['fields'][$field]['del'] === 'Y') {
                $deleteValues[] = $options['fields'][$field]['old_file'];
            }
        }

        foreach ($propertyList as $property) {
            $pId           = (int)$property['ID'];
            $propertyValue = $options['fields']['PROPERTY_VALUES'][$pId];

            foreach ($propertyValue as $vId => $value) {
                if (! is_numeric($vId)) {
                    continue;
                }

                if ($value['VALUE']['del'] === 'Y') {
                    $deleteValues[] = $vId;
                }
            }
        }

        if (! empty($deleteValues)) {
            $imgIds = $this->getRawData([
                'runtime' => [
                    new Reference('PV', ElementCatalogMultiPropertyTable::class, Join::on('this.ID', 'ref.VALUE')),
                ],
                'select'  => [
                    'ID',
                ],
                'filter'  => ['PV.ID' => $deleteValues],
            ])->fetchCollection()->fill('ID');

            $images = array_values($this->getImageData($imgIds));

            foreach ($images as $image) {
                $message = $this->producer->prepareMessage($groupCode, self::EVENT_DELETE, $image);

                if (! $message) {
                    continue;
                }

                $this->producer->send($message, $options['event']);
            }
        }
    }

    /**
     * @param array $options
     *
     * @return void
     */
    public function OnAfterIBlockElementUpdateHandler(array $options): void
    {
        $iblockId        = $options['iblock_id'];
        $elementId       = $options['element_id'];
        $groupCode       = $options['group_code'];
        $updatedValues   = [];
        $imageNames      = [];
        $imageProperties = $this->getIblockImageProperties($iblockId);
        $propertyList    = $this->getIblockPropertyList($iblockId, ['CODE' => $imageProperties]);

        foreach ($this->defaultElementImageFields as $field) {
            $key = $field . '_ID';

            if (! empty($options['fields'][$key])) {
                $updatedValues[] = $options['fields'][$key];
            }
        }

        foreach ($propertyList as $property) {
            $pId            = (int)$property['ID'];
            $propertyValues = $options['fields']['PROPERTY_VALUES'][$pId];

            foreach ($propertyValues as $key => $propertyValue) {
                if (! is_numeric($key) && $propertyValue['VALUE']['name']) {
                    $imageNames[] = $propertyValue['VALUE']['name'];
                }
            }
        }

        if (! empty($imageNames)) {
            $imgIds        = $this->getIdsByNames($imageNames);
            $updatedValues = array_unique(array_merge($updatedValues, $imgIds));
        }

        $images = $this->getImageData($updatedValues);

        foreach ($images as $image) {
            $message = $this->producer->prepareMessage($groupCode, self::EVENT_UPLOAD, $image);

            if (! $message) {
                continue;
            }

            $this->producer->send($message, $options['event']);
        }
    }

    public function fileHandler()
    {
        $store = \Userstory\ItsIntegrator\Settings\Store::getInstance();

        if($storeList = $store->get()) {
            $imageList = $this->prepareImages([
                'filter' => ['ORIGINAL_NAME' => $storeList['imageNameList']],
                'select' => ['ID', 'FILE_NAME', 'SUBDIR', 'ORIGINAL_NAME'],
                'order'  => ['ID' => 'desc'],
            ]);
            if($imageList) {
                foreach ($imageList as $image) {
                    $message = $this->producer->prepareMessage($storeList['group_code'], $storeList['eventName'], $image);
                    if (!$message) {
                        file_put_contents(
                            Application::getDocumentRoot().'/logs/userstory.itsintegrator/sendFile.log',
                            'Для картинки '.$image['ORIGINAL_NAME'].' не сформированно сообщение об отправке.'.PHP_EOL,
                            FILE_APPEND
                        );
                    }

                    $this->producer->send($message, $storeList['event']);
                }
            }
        }
    }

    public function OnIBlockElementSetPropertyValuesExHandler(array $options)
    {
        $extensionList = ['jpg','gif','bmp', 'png', 'jpeg', 'webp'];
        $store = \Userstory\ItsIntegrator\Settings\Store::getInstance();
        if(!$store->get()) {
            $store->setOption([
                'event' => $options['event'],
                'eventName' => self::EVENT_UPLOAD,
                'group_code' => $options['group_code']
            ]);
        }
        foreach ($options['property_list'] as $property) {
            if ($property['PROPERTY_TYPE'] === PropertyTable::TYPE_FILE) {
                $fileList = $options['property_values'][$property['CODE']];
                if ($fileList) {
                    foreach ($fileList as $file) {
                        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        if (in_array($fileExtension, $extensionList)) {
                            $store->setFileName($file['name'], 'imageNameList');
                        } else {
                            file_put_contents(
                                Application::getDocumentRoot().'/logs/userstory.itsintegrator/sendFile.log',
                                'Не верное расширение файла '.$file['name'].PHP_EOL,
                                FILE_APPEND
                            );
                        }
                    }
                }
            }
        }

//
//            $propertyCode = $property['CODE'];
//
//            if (! array_key_exists($propertyCode, $options['property_values'])) {
//                continue;
//            }
//
//            $propertyValues = $options['property_values'][$propertyCode];
//
//            foreach ($propertyValues as $propertyValue) {
//                $imageNames[] = $propertyValue['name'];
//            }
//        }
//
//
//
//
//        if (! empty($imageNames)) {
//            $images = $this->prepareImages([
//                'filter' => ['ORIGINAL_NAME' => $imageNames],
//                'select' => ['ID', 'FILE_NAME', 'SUBDIR', 'ORIGINAL_NAME'],
//                'order'  => ['ID' => 'desc'],
//            ]);
//            // TODO: переделать на нормальную реализацию
//            if(!$images || count($imageNames) !== count($images)) {
//                self::$imageNameStore[$options['iblock_id'].'-'.$options['element_id']] = [
//                    'event' => $options['event'],
//                    'iblock_id' => $options['iblock_id'],
//                    'element_id' => $options['element_id'],
//                    'group_code' => $options['group_code'],
//                    'imageNameList' =>  $imageNames
//                ];
//            } else {
//                foreach ($images as $image) {
//                    $message = $this->producer->prepareMessage($groupCode, self::EVENT_UPLOAD, $image);
//                    if (! $message) {
//                        continue;
//                    }
//
//                    $this->producer->send($message, $options['event']);
//                }
//            }
//        }
    }

    /**
     * @param array $options
     *
     * @return void
     */
    public function OnIBlockElementDeleteHandler(array $options): void
    {
        $images = $this->getElementImages($options['element_id'], $options['iblock_id']);

        foreach ($images as $image) {
            $message = $this->producer->prepareMessage($options['group_code'], self::EVENT_DELETE, $image);

            if (! $message) {
                continue;
            }

            $this->producer->send($message, $options['event']);
        }
    }

    public function getElementImages(int $elementId, int $iblockId, array $select = []): array
    {
        if (! $this->iblock) {
            $this->findIblockById($iblockId);
        }

        if (empty($select)) {
            $select = array_merge($this->defaultElementImageFields, $this->getIblockImageProperties($iblockId));
        }

        $element = $this->iblock->getEntityDataClass()::getByPrimary($elementId, [
            'select' => $select,
        ])->fetch();

        $values   = array_values($element);
        $filtered = array_filter($values, 'is_numeric');

        return $this->getImageData(array_values($filtered));
    }

    public function findIblockById(int $iblockId): IblockHandler
    {
        $this->iblock = Iblock::wakeUp($iblockId);
        return $this;
    }

    /**
     * @param array $options
     *
     * @return void
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function OnAfterIBlockSectionAddHandler(array $options): void
    {
        $imageNames = [];

        foreach ($this->defaultSectionImageFields as $imageField) {
            $field = $options['fields'][$imageField];

            if (! $field) {
                continue;
            }

            $imageNames[] = $field['name'];
        }

        if (! empty($imageNames)) {
            $imgIds = $this->getIdsByNames($imageNames);

            if (! empty($imgIds)) {
                $images = array_values($this->getImageData($imgIds));

                foreach ($images as $image) {
                    $message = $this->producer->prepareMessage($options['group_code'], self::EVENT_UPLOAD, $image);

                    if (! $message) {
                        continue;
                    }

                    $this->producer->send($message, $options['event']);
                }
            }
        }
    }

    /**
     * @param array $options
     *
     * @return void
     */
    public function OnBeforeIBlockSectionUpdateHandler(array $options): void
    {
        $groupCode     = $options['group_code'];
        $deletedImages = [];
        $logData       = [
            'event'     => $options['event'],
            'groupCode' => $options['group_code'],
        ];

        foreach ($this->defaultSectionImageFields as $imageField) {
            $field = $options['fields'][$imageField];

            if ($field['del'] === 'Y') {
                $deletedImages[] = $field['old_file'];
            }
        }

        if (! empty($deletedImages)) {
            $images            = $this->getImageData($deletedImages);
            $logData['images'] = $images;

            foreach ($images as $image) {
                $message = $this->producer->prepareMessage($groupCode, self::EVENT_DELETE, $image);

                if (! $message) {
                    continue;
                }

                $this->producer->send($message, $options['event']);
            }
        }
    }

    /**
     * @param array $options
     *
     * @return void
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function OnAfterIBlockSectionUpdateHandler(array $options)
    {
        $imageNames = [];

        foreach ($this->defaultSectionImageFields as $imageField) {
            $field = $options['fields'][$imageField];

            if ($field['COPY_FILE'] === 'Y') {
                $imageNames[] = $field['name'];
            }
        }

        if (! empty($imageNames)) {
            $imgIds = $this->getIdsByNames($imageNames);

            if (! empty($imgIds)) {
                $images = $this->getImageData($imgIds);

                foreach ($images as $image) {
                    $message = $this->producer->prepareMessage($options['group_code'], self::EVENT_UPLOAD, $image);

                    if (! $message) {
                        continue;
                    }

                    $this->producer->send($message, $options['event']);
                }
            }
        }
    }

    /**
     * @param array $options
     *
     * @return void
     */
    public function OnBeforeIBlockSectionDeleteHandler(array $options)
    {
        $images = $this->getSectionImages($options['section_id'], $options['iblock_id']);

        foreach ($images as $image) {
            $message = $this->producer->prepareMessage($options['group_code'], self::EVENT_DELETE, $image);

            if (! $message) {
                continue;
            }

            $this->producer->send($message, $options['event']);
        }
    }

    public function getSectionImages(int $sectionId, int $iblockId, array $select = []): array
    {
        if (! $this->section) {
            $this->findSectionByIblockId($iblockId);
        }

        if (empty($select)) {
            $select = $this->defaultSectionImageFields;
        }

        $section = $this->section::getByPrimary($sectionId, [
            'select' => $select,
        ])->fetch();

        $values   = array_values($section);
        $filtered = array_filter($values, 'is_numeric');

        return $this->getImageData(array_values($filtered));
    }

    public function findSectionByIblockId(int $iblockId): IblockHandler
    {
        $this->section = Section::compileEntityByIblock($iblockId);
        return $this;
    }

    /**
     * @return array
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function getAllowedIblocksIds(): array
    {
        $data = IblockTable::getList([
            'filter' => ['CODE' => $this->getAllowedIblocksCodes()],
            'select' => ['ID'],
        ])->fetchAll();

        return array_map(function ($item) {
            return (int)$item['ID'];
        }, array_column($data, 'ID'));
    }

    public function getAllowedIblocksCodes(): array
    {
        $codes = Option::get(ItsConnector::MODULE_ID, 'allowed_iblocks_codes');
        return explode(',', $codes);
    }
}
