<?php

namespace Userstory\I18n\Helper;

use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Highloadblock\HighloadBlockRightsTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Localization\Loc;
use CTask;
use Exception;
use Userstory\I18n\Exception\HelperException;
use Userstory\I18n\Helper\Helper;
use Userstory\I18n\Helper\UserTypeEntityHelper;

/**
 * Class HLBlockHelper
 * 
 * @package Userstory\I18n\Helper
 */
class HLBlockHelper extends Helper
{
    /**
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return $this->checkModules(['highloadblock']);
    }

    /**
     * Получает список HL-блоков для основной версии сайта
     * 
     * @return array
     */
    public function getHLBlocksForMainSite(): array
    {
        $siteHelper = new SiteHelper();
        $mainSiteId = $siteHelper->getDefaultSiteIdIfExists();
        $sites      = $siteHelper->getSites();
        
        $filter = [];
        if (count($sites) > 1) {
            $filter[] = array_reduce(
                $sites,
                function ($acc, $site) use ($mainSiteId) {
                    if ($site['LID'] !== $mainSiteId) {
                        $acc[] = [
                            '!TABLE_NAME' => '%_' . strtolower($site['LID'])
                        ];
                    }

                    return $acc;
                },
                ['LOGIC' => 'AND']
            );
        }

        $hlblocks = $this->getHLBlocks($filter);
        return $this->arrangeByKey($hlblocks, 'ID');
    }

    /**
     * Получает список highload-блоков
     *
     * @param array $filter
     *
     * @throws HelperException
     * @return array
     */
    public function getHlblocks($filter = [])
    {
        $result = [];
        try {
            $dbres = HighloadBlockTable::getList(
                [
                    'select' => ['*'],
                    'filter' => $filter,
                ]
            );
            while ($hlblock = $dbres->fetch()) {
                $result[] = $this->prepareHlblock($hlblock);
            }
        } catch (Exception $e) {
            $this->throwException(
                __METHOD__,
                $e->getMessage()
            );
        }
        return $result;
    }

    /**
     * Получает список highload-блоков
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param array $filter
     *
     * @throws HelperException
     * @return array
     */
    public function exportHlblocks($filter = [])
    {
        $items = $this->getHlblocks($filter);

        $export = [];
        foreach ($items as $item) {
            $export[] = $this->prepareExportHlblock($item);
        }

        return $export;
    }

    /**
     * Получает список полей highload-блока
     *
     * @param $hlblockName int|string|array - id, имя или фильтр
     *
     * @throws HelperException
     * @return array
     */
    public function getFields($hlblockName)
    {
        $entityHelper = new UserTypeEntityHelper();
        return $entityHelper->getUserTypeEntities(
            $this->getEntityId($hlblockName)
        );
    }

    /**
     * Получает поле highload-блока
     *
     * @param $hlblockName
     * @param $fieldName
     *
     * @throws HelperException
     * @return array|bool
     */
    public function getField($hlblockName, $fieldName)
    {
        $entityHelper = new UserTypeEntityHelper();

        return $entityHelper->getUserTypeEntity(
            $this->getEntityId($hlblockName),
            $fieldName
        );
    }

    /**
     * @param $hlblockName
     * @param $field
     *
     * @throws HelperException
     * @return string|void
     */
    public function getFieldUid($hlblockName, $field)
    {
        $entityHelper = new UserTypeEntityHelper();

        if (!is_array($field)) {
            //на вход пришел id или название поля
            if (is_numeric($field)) {
                $field = $entityHelper->getUserTypeEntityById($field);
            } else {
                $field = $entityHelper->getUserTypeEntity(
                    $this->getEntityId($hlblockName),
                    $field
                );
            }
        }

        if (!empty($field['FIELD_NAME'])) {
            return $field['FIELD_NAME'];
        }
        $this->throwException(__METHOD__, Loc::getMessage(self::MESSAGE_PREFIX . 'ERR_HLBLOCK_FIELD_NOT_FOUND'));
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     *
     * @throws HelperException
     * @return mixed
     */
    public function getFieldType($hlblockName, $fieldName)
    {
        $field = $this->getField($hlblockName, $fieldName);
        return $field['USER_TYPE_ID'];
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     *
     * @throws HelperException
     * @return bool
     */
    public function isFieldMultiple($hlblockName, $fieldName)
    {
        $field = $this->getField($hlblockName, $fieldName);
        return ($field['MULTIPLE'] == 'Y');
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     * @param $xmlId
     *
     * @throws HelperException
     * @return mixed|string
     */
    public function getFieldEnumIdByXmlId($hlblockName, $fieldName, $xmlId)
    {
        $field = $this->getField($hlblockName, $fieldName);
        if (empty($field['ENUM_VALUES']) || !is_array($field['ENUM_VALUES'])) {
            return '';
        }

        foreach ($field['ENUM_VALUES'] as $val) {
            if ($val['XML_ID'] == $xmlId) {
                return $val['ID'];
            }
        }

        return '';
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     * @param $id
     *
     * @throws HelperException
     * @return mixed|string
     */
    public function getFieldEnumXmlIdById($hlblockName, $fieldName, $id)
    {
        $field = $this->getField($hlblockName, $fieldName);
        if (empty($field['ENUM_VALUES']) || !is_array($field['ENUM_VALUES'])) {
            return '';
        }

        foreach ($field['ENUM_VALUES'] as $val) {
            if ($val['ID'] == $id) {
                return $val['XML_ID'];
            }
        }
        return '';
    }

    /**
     * @param $hlblockName
     * @param $fieldUid
     *
     * @throws HelperException
     * @return int
     */
    public function getFieldIdByUid($hlblockName, $fieldUid)
    {
        $fieldId = 0;

        if (empty($fieldUid)) {
            return $fieldId;
        }

        if (is_numeric($fieldUid)) {
            return $fieldUid;
        }

        $field = $this->getField($hlblockName, $fieldUid);

        return ($field) ? (int)$field['ID'] : 0;
    }

    /**
     * @param $hlblockName
     *
     * @throws HelperException
     * @return string
     */
    public function getEntityId($hlblockName)
    {
        $hlblockId = is_numeric($hlblockName) ? $hlblockName : $this->getHlblockId($hlblockName);
        return 'HLBLOCK_' . $hlblockId;
    }

    /**
     * Удаляет поле highload-блока
     *
     * @param $hlblockName
     * @param $fieldName
     *
     * @throws HelperException
     * @return bool
     */
    public function deleteField($hlblockName, $fieldName)
    {
        $entityHelper = new UserTypeEntityHelper();
        return $entityHelper->deleteUserTypeEntity(
            $this->getEntityId($hlblockName),
            $fieldName
        );
    }

    /**
     * Получает список полей highload-блока
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param $hlblockName
     *
     * @throws HelperException
     * @return array
     */
    public function exportFields($hlblockName)
    {
        $entityHelper = new UserTypeEntityHelper();

        $fields = $entityHelper->exportUserTypeEntities(
            $this->getEntityId($hlblockName)
        );

        foreach ($fields as $index => $field) {
            unset($field['ENTITY_ID']);
            $fields[$index] = $field;
        }

        return $fields;
    }

    /**
     * Получает highload-блок
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param $hlblockName
     *
     * @throws HelperException
     * @return mixed
     */
    public function exportHlblock($hlblockName)
    {
        return $this->prepareExportHlblock(
            $this->getHlblock($hlblockName)
        );
    }

    /**
     * Получает highload-блок
     *
     * @param $hlblockName - id, имя или фильтр
     *
     * @throws HelperException
     * @return array|false
     */
    public function getHlblock($hlblockName)
    {
        if (is_array($hlblockName)) {
            $filter = $hlblockName;
        } elseif (is_numeric($hlblockName)) {
            $filter = ['ID' => $hlblockName];
        } else {
            $filter = ['NAME' => $hlblockName];
        }

        try {
            $hlblock = HighloadBlockTable::getList(
                [
                    'select' => ['*'],
                    'filter' => $filter,
                ]
            )->fetch();

            return $this->prepareHlblock($hlblock);
        } catch (Exception $e) {
            $this->throwException(__METHOD__, $e->getMessage());
        }

        return false;
    }

    /**
     * @param $hlblockName
     *
     * @throws HelperException
     * @return array|void
     */
    public function getHlblockIfExists($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item;
        }

        $this->throwException(
            __METHOD__,
            Loc::getMessage(
                self::MESSAGE_PREFIX . 'ERR_HLBLOCK_NOT_FOUND',
                ['#HLBLOCK#' => is_array($hlblockName) ? var_export($hlblockName, true) : $hlblockName]
            )
        );
    }

    /**
     * Получает highload-блок, бросает исключение если его не существует
     *
     * @param $hlblockName - id, имя или фильтр
     *
     * @throws HelperException
     * @return int|void
     */
    public function getHlblockIdIfExists($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }

        $this->throwException(
            __METHOD__,
            Loc::getMessage(
                self::MESSAGE_PREFIX . 'ERR_HLBLOCK_NOT_FOUND',
                ['#HLBLOCK#' => is_array($hlblockName) ? var_export($hlblockName, true) : $hlblockName]
            )
        );
    }

    /**
     * Получает id highload-блока
     *
     * @param $hlblockName - id, имя или фильтр
     *
     * @throws HelperException
     * @return int|mixed
     */
    public function getHlblockId($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Добавляет highload-блок
     *
     * @param array $fields
     *
     * @throws HelperException
     * @return int|void
     */
    public function addHlblock($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['NAME', 'TABLE_NAME']);
        $fields['NAME'] = ucfirst($fields['NAME']);

        $lang = [];
        if (isset($fields['LANG'])) {
            $lang = $fields['LANG'];
            unset($fields['LANG']);
        }

        try {
            $result = HighloadBlockTable::add($fields);
            if ($result->isSuccess()) {
                $this->replaceHblockLangs($result->getId(), $lang);
                return $result->getId();
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            $this->throwException(__METHOD__, $e->getMessage());
        }
    }

    /**
     * Добавляет highload-блок, если его не существует
     *
     * @param array $fields
     *
     * @throws HelperException
     * @return int|mixed
     */
    public function addHlblockIfNotExists($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['NAME']);

        $item = $this->getHlblock($fields['NAME']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addHlblock($fields);
    }

    /**
     * Обновляет highload-блок
     *
     * @param $hlblockId
     * @param $fields
     *
     * @throws HelperException
     * @return int|void
     */
    public function updateHlblock($hlblockId, $fields)
    {
        $lang = [];
        if (isset($fields['LANG'])) {
            $lang = $fields['LANG'];
            unset($fields['LANG']);
        }

        try {
            $result = HighloadBlockTable::update($hlblockId, $fields);

            if ($result->isSuccess()) {
                $this->replaceHblockLangs($hlblockId, $lang);
                return $hlblockId;
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            $this->throwException(__METHOD__, $e->getMessage());
        }
    }

    /**
     * Обновляет highload-блок, если существует
     *
     * @param $hlblockName
     * @param $fields
     *
     * @throws HelperException
     * @return bool|int
     */
    public function updateHlblockIfExists($hlblockName, $fields)
    {
        $item = $this->getHlblock($hlblockName);
        if (!$item) {
            return false;
        }

        return $this->updateHlblock($item['ID'], $fields);
    }

    /**
     * Удаляет highload-блок
     *
     * @param $hlblockId
     *
     * @throws HelperException
     * @return bool|void
     */
    public function deleteHlblock($hlblockId)
    {
        try {
            $result = HighloadBlockTable::delete($hlblockId);
            if ($result->isSuccess()) {
                return true;
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            $this->throwException(__METHOD__, $e->getMessage());
        }
    }

    /**
     * Удаляет highload-блок, если существует
     *
     * @param $hlblockName
     *
     * @throws HelperException
     * @return bool
     */
    public function deleteHlblockIfExists($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        if (!$item) {
            return false;
        }

        return $this->deleteHlblock($item['ID']);
    }

    /**
     * Получает права доступа к highload-блоку для групп
     * возвращает массив вида [$groupId => $letter]
     *
     * @param $hlblockId
     *
     * @return array
     */
    public function getGroupPermissions($hlblockId)
    {
        $permissions = [];
        $rights = $this->getGroupRights($hlblockId);
        foreach ($rights as $right) {
            $permissions[$right['GROUP_ID']] = $right['LETTER'];
        }
        return $permissions;
    }

    /**
     * @param $hlblockId
     *
     * @throws HelperException
     * @return array
     */
    public function exportGroupPermissions($hlblockId)
    {
        $groupHelper = new UserGroupHelper();
        $permissions = $this->getGroupPermissions($hlblockId);

        $result = [];
        foreach ($permissions as $groupId => $letter) {
            $groupCode = $groupHelper->getGroupCode($groupId);
            $groupCode = !empty($groupCode) ? $groupCode : $groupId;
            $result[$groupCode] = $letter;
        }

        return $result;
    }

    public function saveGroupPermissions($hlblockId, $permissions = [])
    {
        $groupHelper = new UserGroupHelper();

        $result = [];
        foreach ($permissions as $groupCode => $letter) {
            $groupId = is_numeric($groupCode) ? $groupCode : $groupHelper->getGroupId($groupCode);
            $result[$groupId] = $letter;
        }

        $this->setGroupPermissions($hlblockId, $result);
    }

    /**
     * Устанавливает права доступа к highload-блоку для групп
     * предыдущие права сбрасываются
     * принимает массив вида [$groupId => $letter]
     *
     * @param       $hlblockId
     * @param array $permissions
     *
     * @return bool
     */
    public function setGroupPermissions($hlblockId, $permissions = [])
    {
        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockRightsTable')) {
            return false;
        }

        $rights = $this->getGroupRights($hlblockId);

        try {
            foreach ($rights as $right) {
                HighloadBlockRightsTable::delete($right['ID']);
            }

            foreach ($permissions as $groupId => $letter) {
                $taskId = CTask::GetIdByLetter($letter, 'highloadblock');

                if (!empty($taskId)) {
                    HighloadBlockRightsTable::add(
                        [
                            'HL_ID'       => $hlblockId,
                            'TASK_ID'     => $taskId,
                            'ACCESS_CODE' => 'G' . $groupId,
                        ]
                    );
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param        $hlblock
     *
     * @throws HelperException
     * @return string|void
     */
    public function getHlblockUid($hlblock)
    {
        if (!is_array($hlblock)) {
            // если хайлоадблок не найден, надо показать что искали
            $getHlblock = $this->getHlblock($hlblock);

            if (false === $getHlblock) {
                $this->throwException(
                    __METHOD__,
                    Loc::getMessage(
                        self::MESSAGE_PREFIX . 'ERR_HLBLOCK_NOT_FOUND',
                        ['#HLBLOCK#' => $hlblock]
                    )
                );
            }

            $hlblock = $getHlblock;
        }

        if (!empty($hlblock['NAME'])) {
            return $hlblock['NAME'];
        }

        $this->throwException(
            __METHOD__,
            Loc::getMessage(
                self::MESSAGE_PREFIX . 'ERR_HLBLOCK_NOT_FOUND',
                ['#HLBLOCK#' => is_array($hlblock) ? var_export($hlblock, true) : $hlblock]
            )
        );
    }

    /**
     * @param $hlblockUid
     *
     * @throws HelperException
     * @return int
     */
    public function getHlblockIdByUid($hlblockUid)
    {
        $hlblockId = 0;
        if (empty($hlblockUid)) {
            return $hlblockId;
        }

        return $this->getHlblockId($hlblockUid);
    }

    /**
     * @param $item
     *
     * @return mixed
     */
    protected function prepareHlblock($item)
    {
        if (empty($item['ID'])) {
            return $item;
        }

        $langs = $this->getHblockLangs($item['ID']);
        if (!empty($langs)) {
            $item['LANG'] = $langs;
        }

        return $item;
    }

    /**
     * @param int $hlblockId
     *
     * @return array
     */
    protected function getGroupRights($hlblockId)
    {
        $result = [];
        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockRightsTable')) {
            return $result;
        }

        try {
            $items = HighloadBlockRightsTable::getList(
                [
                    'filter' => [
                        'HL_ID' => $hlblockId,
                    ],
                ]
            )->fetchAll();
        } catch (Exception $e) {
            $items = [];
        }

        foreach ($items as $item) {
            if (strpos($item['ACCESS_CODE'], 'G') !== 0) {
                continue;
            }

            $groupId = (int)substr($item['ACCESS_CODE'], 1);
            if (empty($groupId)) {
                continue;
            }

            $letter = CTask::GetLetter($item['TASK_ID']);
            if (empty($letter)) {
                continue;
            }

            $item['LETTER'] = $letter;
            $item['GROUP_ID'] = $groupId;

            $result[] = $item;
        }

        return $result;
    }

    protected function prepareExportHlblock($item)
    {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);

        return $item;
    }

    /**
     * @param int $hlblockId
     *
     * @return array
     */
    protected function getHblockLangs($hlblockId)
    {
        $result = [];

        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $result;
        }

        try {
            $dbres = HighloadBlockLangTable::getList(
                [
                    'filter' => ['ID' => $hlblockId],
                ]
            );

            while ($item = $dbres->fetch()) {
                $result[$item['LID']] = [
                    'NAME' => $item['NAME'],
                ];
            }
        } catch (Exception $e) {
        }

        return $result;
    }

    /**
     * @param int $hlblockId
     *
     * @throws Exception
     * @return int
     */
    protected function deleteHblockLangs($hlblockId)
    {
        $del = 0;

        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $del;
        }

        try {
            $items = HighloadBlockLangTable::getList(
                [
                    'filter' => ['ID' => $hlblockId],
                ]
            )->fetchAll();
        } catch (Exception $e) {
            $items = [];
        }

        foreach ($items as $item) {
            HighloadBlockLangTable::delete($item['ID']);
            $del++;
        }

        return $del;
    }

    /**
     * @param int   $hlblockId
     * @param array $lang
     *
     * @throws Exception
     * @return int
     */
    protected function addHblockLangs($hlblockId, $lang = [])
    {
        $add = 0;

        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $add;
        }

        foreach ($lang as $lid => $item) {
            if (!empty($item['NAME'])) {
                HighloadBlockLangTable::add(
                    [
                        'ID'   => $hlblockId,
                        'LID'  => $lid,
                        'NAME' => $item['NAME'],
                    ]
                );

                $add++;
            }
        }

        return $add;
    }

    /**
     * @param int   $hlblockId
     * @param array $lang
     *
     * @throws Exception
     */
    protected function replaceHblockLangs($hlblockId, $lang = [])
    {
        if (!empty($lang) && is_array($lang)) {
            $this->deleteHblockLangs($hlblockId);
            $this->addHblockLangs($hlblockId, $lang);
        }
    }
}
