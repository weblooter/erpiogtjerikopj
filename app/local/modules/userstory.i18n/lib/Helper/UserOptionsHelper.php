<?php

namespace Userstory\I18n\Helper;

use CGridOptions;
use CIBlock;
use CUserOptions;
use Userstory\I18n\Helper\Helper;
use Userstory\I18n\Helper\IblockHelper;

class UserOptionsHelper extends Helper
{
    /**
     * @var array
     */
    private array $titles = [];

    /**
     * @var array
     */
    private array $props = [];

    /**
     * @var array
     */
    private array $iblock = [];

    /**
     * @var int
     */
    private int $lastIblockId = 0;

    /**
     * @param int|string $iblockId
     * 
     * @return array
     */
    public function exportElementForm($iblockId): array
    {
        $this->initializeIblockVars($iblockId);

        return $this->exportForm([
            'name' => 'form_element_' . $iblockId,
        ]);
    }

    /**
     * @param int|string $iblockId
     * @param array $formData
     * 
     * @return bool
     */
    public function buildElementForm($iblockId, array $formData = []): bool
    {
        $this->initializeIblockVars($iblockId);

        return $this->buildForm($formData, [
            'name' => 'form_element_' . $iblockId,
        ]);
    }

    /**
     * @param int|string $iblockId
     * 
     * @return array
     */
    public function exportSectionForm($iblockId): array
    {
        $this->initializeIblockVars($iblockId);

        return $this->exportForm([
            'name' => 'form_section_' . $iblockId,
        ]);
    }

    /**
     * @param int|string $iblockId
     * @param array $formData
     * 
     * @return bool
     */
    public function buildSectionForm($iblockId, array $formData = []): bool
    {
        $this->initializeIblockVars($iblockId);

        return $this->buildForm($formData, [
            'name' => 'form_section_' . $iblockId,
        ]);
    }

    /**
     * @param int|string $iblockId
     * 
     * @return bool
     */
    private function initializeIblockVars($iblockId): bool
    {
        $iblockHelper = new IblockHelper();

        if ($this->lastIblockId == $iblockId) {
            return true;
        }

        $iblock = $iblockHelper->getIblockIfExists($iblockId);

        $this->lastIblockId = $iblockId;
        $this->iblock = $iblock;
        $this->props = [];
        $this->titles = [];

        $props = $iblockHelper->getProperties($iblockId);
        foreach ($props as $prop) {
            if (!empty($prop['CODE'])) {
                $this->titles['PROPERTY_' . $prop['ID']] = $prop['NAME'];
                $this->props[] = $prop;
            }
        }

        $iblockMess = IncludeModuleLangFile('/bitrix/modules/iblock/iblock.php', 'ru', true);

        $this->titles['ACTIVE_FROM'] = $iblockMess['IBLOCK_FIELD_ACTIVE_PERIOD_FROM'];
        $this->titles['ACTIVE_TO'] = $iblockMess['IBLOCK_FIELD_ACTIVE_PERIOD_TO'];

        foreach ($iblockMess as $code => $value) {
            if (false !== strpos($code, 'IBLOCK_FIELD_')) {
                $fcode = str_replace('IBLOCK_FIELD_', '', $code);
                $this->titles[$fcode] = $value;
            }
        }

        return true;
    }

    /**
     * @param array $params
     * 
     * @return array
     */
    public function exportForm(array $params = []): array
    {
        $params = array_merge(
            [
                'name' => '',
                'category' => 'form',
            ],
            $params
        );

        $option = CUserOptions::GetOption(
            $params['category'],
            $params['name'],
            false,
            false
        );

        $extractedTabs = [];

        if (!$option || empty($option['tabs'])) {
            return $extractedTabs;
        }

        $optionTabs = explode(';', $option['tabs']);

        foreach ($optionTabs as $tabStrings) {
            $extractedFields = [];
            $tabTitle = '';
            $tabId = '';

            $columnString = explode(',', $tabStrings);

            foreach ($columnString as $fieldIndex => $fieldString) {
                if (!strpos($fieldString, '#')) {
                    continue;
                }

                list($fieldCode, $fieldTitle) = explode('#', $fieldString);

                $fieldCode = str_replace('--', '', strval($fieldCode));
                $fieldTitle = str_replace('--', '', strval($fieldTitle));

                $fieldCode = trim($fieldCode, '*');
                $fieldTitle = trim($fieldTitle, '*');

                if ($fieldIndex == 0) {
                    $tabTitle = $fieldTitle;
                    $tabId = $fieldCode;
                } else {
                    $fieldCode = $this->revertCode($fieldCode);
                    $extractedFields[$fieldCode] = $fieldTitle;
                }
            }

            if ($tabTitle) {
                $extractedTabs[$tabTitle . '|' . $tabId] = $extractedFields;
            }
        }

        return $extractedTabs;
    }

    /**
     * @param string $fieldCode
     * 
     * @return string
     */
    private function revertCode(string $fieldCode): string
    {
        if (0 === strpos($fieldCode, 'PROPERTY_')) {
            $fieldCode = substr($fieldCode, 9);
            foreach ($this->props as $prop) {
                if ($prop['ID'] == $fieldCode) {
                    $fieldCode = $prop['CODE'];
                    break;
                }
            }
            $fieldCode = 'PROPERTY_' . $fieldCode;
        }

        return $fieldCode;
    }

    /**
     * @param array $formData
     * @param array $params
     * 
     * @return bool
     */
    public function buildForm(array $formData = [], array $params = []): bool
    {
        $params = array_merge(
            [
                'name' => '',
                'category' => 'form',
            ],
            $params
        );

        if (empty($formData)) {
            CUserOptions::DeleteOptionsByName(
                $params['category'],
                $params['name']
            );
            return true;
        }

        $tabIndex = 0;
        $tabVals = [];

        foreach ($formData as $tabTitle => $fields) {
            list($tabTitle, $tabId) = explode('|', $tabTitle);

            if (!$tabId) {
                $tabId = 'edit' . ($tabIndex + 1);
            }

            $tabId = ($tabIndex == 0) ? $tabId : '--' . $tabId;

            $tabVals[$tabIndex][] = $tabId . '--#--' . $tabTitle . '--';

            foreach ($fields as $fieldKey => $fieldValue) {
                if (is_numeric($fieldKey)) {
                    /** @compability */
                    list($fcode, $ftitle) = explode('|', $fieldValue);
                } else {
                    $fcode = $fieldKey;
                    $ftitle = $fieldValue;
                }

                $fcode = $this->transformCode($fcode);
                $ftitle = $this->prepareTitle($fcode, $ftitle);

                $tabVals[$tabIndex][] = '--' . $fcode . '--#--' . $ftitle . '--';
            }

            $tabIndex++;
        }

        $opts = [];
        foreach ($tabVals as $fields) {
            $opts[] = implode(',', $fields);
        }

        $opts = implode(';', $opts) . ';--';

        $value = [
            'tabs' => $opts,
        ];

        CUserOptions::DeleteOptionsByName(
            $params['category'],
            $params['name']
        );
        CUserOptions::SetOption(
            $params['category'],
            $params['name'],
            $value,
            true
        );

        return true;
    }

    /**
     * Возвращает название поля формы
     * 
     * @param string $fieldCode
     * @param string $fieldTitle
     * 
     * @return string
     */
    private function prepareTitle(string $fieldCode, string $fieldTitle = ''): string
    {
        if (!empty($fieldTitle)) {
            return $fieldTitle;
        }

        if (isset($this->titles[$fieldCode])) {
            return $this->titles[$fieldCode];
        }

        return $fieldCode;
    }

    /**
     * @param string $fieldCode
     * 
     * @return string
     */
    private function transformCode(string $fieldCode): string
    {
        if (0 === strpos($fieldCode, 'PROPERTY_')) {
            $fieldCode = substr($fieldCode, 9);
            foreach ($this->props as $prop) {
                if ($prop['CODE'] == $fieldCode) {
                    $fieldCode = $prop['ID'];
                    break;
                }
            }
            $fieldCode = 'PROPERTY_' . $fieldCode;
        }
        return $fieldCode;
    }

    /**
     * @param int|string $iblockId
     * 
     * @return array
     */
    public function exportElementGrid($iblockId): array
    {
        return $this->exportGrid($this->getElementGridId($iblockId));
    }

    /**
     * @param int|string $iblockId
     * 
     * @return string
     */
    public function getElementGridId($iblockId): string
    {
        $this->initializeIblockVars($iblockId);

        if (CIBlock::GetAdminListMode($iblockId) == 'S') {
            $prefix = defined('CATALOG_PRODUCT') ? 'tbl_product_admin_' : 'tbl_iblock_element_';
        } else {
            $prefix = defined('CATALOG_PRODUCT') ? 'tbl_product_list_' : 'tbl_iblock_list_';
        }

        return $prefix . md5($this->iblock['IBLOCK_TYPE_ID'] . '.' . $iblockId);
    }

    /**
     * @param int|string $iblockId
     * 
     * @return array
     */
    public function exportSectionGrid($iblockId): array
    {
        return $this->exportGrid($this->getSectionGridId($iblockId));
    }

    /**
     * @param int|string $iblockId
     * 
     * @return string
     */
    public function getSectionGridId($iblockId): string
    {
        $this->initializeIblockVars($iblockId);
        return 'tbl_iblock_section_' . md5($this->iblock['IBLOCK_TYPE_ID'] . '.' . $iblockId);
    }

    /**
     * @param string $gridId
     * 
     * @return array
     */
    public function exportGrid(string $gridId): array
    {
        $params = CUserOptions::GetOption(
            "main.interface.grid",
            $gridId,
            []
        );
        if (!empty($params)) {
            $options = (new CGridOptions($gridId))->GetOptions();

            foreach ($options['views'] as $viewCode => $view) {
                $view['columns'] = $this->revertCodesFromColumns($view['columns']);
                $options['views'][$viewCode] = $view;
            }

            return $options;
        }
        return [];
    }

    /**
     * @param mixed $columns
     * 
     * @return array
     */
    private function revertCodesFromColumns($columns): array
    {
        if (!is_array($columns)) {
            $columns = explode(',', $columns);
            foreach ($columns as $index => $columnCode) {
                $columns[$index] = $this->revertCode($columnCode);
            }
            return $columns;
        }
        return $columns;
    }

    /**
     * @param string $gridId
     * @param array $options
     * 
     * @return bool
     */
    public function buildGrid(string $gridId, array $options = []): bool
    {
        foreach ($options['views'] as $viewCode => $view) {
            $view['columns'] = $this->transformCodesToColumns($view['columns']);
            $options['views'][$viewCode] = $view;
        }

        CUserOptions::DeleteOptionsByName(
            'main.interface.grid',
            $gridId
        );
        CUserOptions::setOption(
            "main.interface.grid",
            $gridId,
            $options,
            true
        );

        return true;
    }

    /**
     * @param mixed $columns
     * 
     * @return string
     */
    private function transformCodesToColumns($columns): string
    {
        if (is_array($columns)) {
            foreach ($columns as $index => $columnCode) {
                $columns[$index] = $this->transformCode($columnCode);
            }
            return implode(',', $columns);
        }
        return $columns;
    }
}
