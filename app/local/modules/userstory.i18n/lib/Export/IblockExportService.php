<?php

namespace Userstory\I18n\Export;

use Userstory\I18n\Helper\IblockHelper;
use Userstory\I18n\Helper\UserOptionsHelper;
use Userstory\I18n\Helper\UserTypeEntityHelper;

/**
 * Class IblockExportService
 *
 * @package Userstory\I18n\Export
 */
class IblockExportService
{
    /**
     * @var IblockHelper
     */
    private IblockHelper $iblockHelper;

    /**
     * @var UserTypeEntityHelper
     */
    private UserTypeEntityHelper $userTypeEntityHelper;

    /**
     * @var UserOptionsHelper
     */
    private UserOptionsHelper $userOptionsHelper;

    /**
     * IblockExportService constructor
     */
    public function __construct()
    {
        $this->iblockHelper = new IblockHelper();
        $this->userTypeEntityHelper = new UserTypeEntityHelper();
        $this->userOptionsHelper = new UserOptionsHelper();
    }
    
    /**
     * @param int $iblockId
     * @param string $siteTo
     * 
     * @return void
     */
    public function export(int $iblockId, string $siteTo)
    {
        global $propertiesWithLinks , $userFieldsWithLinks;

        $iblockData = $this->iblockHelper->exportIblock($iblockId);
        $iblockData = $this->prepareIblockData($iblockData, $siteTo);
        
        $newIblockId = $this->iblockHelper->addIblock($iblockData);

        $iblockFields = $this->iblockHelper->exportIblockFields($iblockId);
        $this->iblockHelper->updateIblockFields($newIblockId, $iblockFields);

        $iblockPermissions = $this->iblockHelper->exportGroupPermissions($iblockId);
        $this->iblockHelper->saveGroupPermissions($newIblockId, $iblockPermissions);

        $iblockProperties = $this->iblockHelper->exportProperties($iblockId);
        foreach ($iblockProperties as $iblockProperty) {
            $propId = $this->iblockHelper->addProperty($newIblockId, $iblockProperty);
            if (
                (in_array($iblockProperty['PROPERTY_TYPE'], ['E', 'G']) && !empty($iblockProperty['LINK_IBLOCK_ID'])) ||
                (($iblockProperty['PROPERTY_TYPE'] === 'S') && ($iblockProperty['USER_TYPE'] === 'directory'))
            ) {
                $propertiesWithLinks[$propId] = $iblockProperty;
            }
        }

        $iblockSectionUserFields = $this->userTypeEntityHelper->exportUserTypeEntities('IBLOCK_' . $iblockId . '_SECTION');
        foreach ($iblockSectionUserFields as $iblockSectionUserField) {
            $userFieldId = $this->userTypeEntityHelper->addUserTypeEntity(
                'IBLOCK_' . $newIblockId . '_SECTION',
                $iblockSectionUserField['FIELD_NAME'],
                $iblockSectionUserField
            );
            if (in_array($iblockSectionUserField['USER_TYPE_ID'], ['iblock_section', 'iblock_element', 'hlblock'])) {
                $userFieldsWithLinks[$userFieldId] = $iblockSectionUserField;
            }
        }

        $elementFormData = $this->userOptionsHelper->exportElementForm($iblockId);
        $this->userOptionsHelper->buildElementForm($newIblockId, $elementFormData);

        $sectionFormData = $this->userOptionsHelper->exportSectionForm($iblockId);
        $this->userOptionsHelper->buildSectionForm($newIblockId, $sectionFormData);

        $elementOptions = $this->userOptionsHelper->exportElementGrid($iblockId);
        $this->userOptionsHelper->buildGrid($this->userOptionsHelper->getElementGridId($newIblockId), $elementOptions);

        $sectionOptions = $this->userOptionsHelper->exportSectionGrid($iblockId);
        $this->userOptionsHelper->buildGrid($this->userOptionsHelper->getSectionGridId($newIblockId), $sectionOptions);

        return $newIblockId;
    }

    /**
     * @param array $data
     * @param string $siteTo
     * 
     * @return array
     */
    private function prepareIblockData(array $data, string $siteTo): array
    {
        $result = $data;
        $result['LID'] = [$siteTo];
        $result['CODE'] = $data['CODE'] . '_' . strtolower($siteTo);
        if (!empty($data['API_CODE'])) {
            $result['API_CODE'] = ucfirst($data['API_CODE']) . ucfirst(strtolower($siteTo));
        }
        $result['NAME'] = $data['NAME'] . ' (' . strtoupper($siteTo) . ')';
        
        return $result;
    }

    /**
     * @param array $exportedIblocks
     * 
     * @return void
     */
    public function addCatalogIblocksIfNeeds(array $exportedIblocks): void
    {
        $catalogIblocks = $this->iblockHelper->getCatalogIblocks(['IBLOCK_ID' => array_keys($exportedIblocks)]);

        foreach ($catalogIblocks as $iblockId => $arItem) {
            $newIblockId = $exportedIblocks[$iblockId];

            if (($arItem['PRODUCT_IBLOCK_ID'] > 0) && key_exists($arItem['PRODUCT_IBLOCK_ID'], $exportedIblocks)) {
                $arItem['PRODUCT_IBLOCK_ID'] = $exportedIblocks[$arItem['PRODUCT_IBLOCK_ID']];
            } else {
                $arItem['PRODUCT_IBLOCK_ID'] = 0;
            }

            if ($arItem['SKU_PROPERTY_ID'] > 0) {
                $arItem['SKU_PROPERTY_ID'] = $this->iblockHelper->getPropertyId($newIblockId, 'CML2_LINK');
            } else {
                $arItem['SKU_PROPERTY_ID'] = 0;
            }

            $this->iblockHelper->addCatalogIblock($newIblockId, $arItem);            
        }
    }
}
