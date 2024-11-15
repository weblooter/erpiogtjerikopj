<?php

namespace Userstory\I18n\Export;

use Userstory\I18n\Helper\HLBlockHelper;
use Userstory\I18n\Helper\UserTypeEntityHelper;

/**
 * Class HLBlockExportService
 *
 * @package Userstory\I18n\Export
 */
class HLBlockExportService
{
    /**
     * @var HLBlockHelper
     */
    private HLBlockHelper $hlblockHelper;

    /**
     * @var UserTypeEntityHelper
     */
    private UserTypeEntityHelper $userTypeEntityHelper;

    /**
     * HLBlockExportService constructor
     */
    public function __construct()
    {
        $this->hlblockHelper = new HLBlockHelper();
        $this->userTypeEntityHelper = new UserTypeEntityHelper();
    }
    
    /**
     * @param int $hlblockId
     * @param string $siteTo
     * 
     * @return void
     */
    public function export(int $hlblockId, string $siteTo)
    {
        global $userFieldsWithLinks;

        $hlblockData = $this->hlblockHelper->exportHlblock($hlblockId);
        $hlblockData = $this->prepareHLBlockData($hlblockData, $siteTo);
        
        $newHlblockId = $this->hlblockHelper->addHlblock($hlblockData);

        $hlblockPermissions = $this->hlblockHelper->exportGroupPermissions($hlblockId);
        $this->hlblockHelper->saveGroupPermissions($newHlblockId, $hlblockPermissions);

        $hlblockFields = $this->hlblockHelper->exportFields($hlblockId);
        foreach ($hlblockFields as $hlblockField) {
            $userFieldId = $this->userTypeEntityHelper->addUserTypeEntity(
                $this->hlblockHelper->getEntityId($newHlblockId),
                $hlblockField['FIELD_NAME'],
                $hlblockField
            );
            if (in_array($hlblockField['USER_TYPE_ID'], ['iblock_section', 'iblock_element', 'hlblock'])) {
                $userFieldsWithLinks[$userFieldId] = $hlblockField;
            }
        }

        return $newHlblockId;
    }

    /**
     * @param array $data
     * @param string $siteTo
     * 
     * @return array
     */
    private function prepareHLBlockData(array $data, string $siteTo): array
    {
        $result = $data;
        $result['NAME'] = $data['NAME'] . ucfirst(strtolower($siteTo));
        $result['TABLE_NAME'] = $data['TABLE_NAME'] . '_' . strtolower($siteTo);
        foreach ($data['LANG'] as $lang => $langData) {
            if (!empty($langData['NAME'])) {
                $result['LANG'][$lang]['NAME'] = $langData['NAME'] . ' (' . strtoupper($siteTo) . ')';
            }
        }
        
        return $result;
    }
}
