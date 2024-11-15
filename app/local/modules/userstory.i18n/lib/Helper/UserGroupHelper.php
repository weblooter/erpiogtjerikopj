<?php

namespace Userstory\I18n\Helper;

use Bitrix\Main\Localization\Loc;
use CGroup;
use Userstory\I18n\Exception\HelperException;
use Userstory\I18n\Helper\Helper;

class UserGroupHelper extends Helper
{
    protected const ADMIN_GROUP_ID = 1;
    protected const ALL_USERS_GROUP_ID = 2;

    /**
     * Получает список групп пользователей
     *
     * @param array $filter
     *
     * @return array
     */
    public function getGroups($filter = [])
    {
        $by = 'c_sort';
        $order = 'asc';

        $res = [];

        $dbres = CGroup::GetList($by, $order, $filter);
        while ($item = $dbres->Fetch()) {
            $res[] = $this->getGroup($item['ID']);
        }

        return $res;
    }

    /**
     * Получает группу пользователей
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param $code
     *
     * @throws HelperException
     * @return array|void
     */
    public function exportGroup($code)
    {
        $item = $this->prepareExportGroup(
            $this->getGroup($code)
        );

        if (!empty($item['STRING_ID'])) {
            return $item;
        }

        $this->throwException(
            __METHOD__,
            Loc::getMessage(
                self::MESSAGE_PREFIX . 'ERR_USER_GROUP_CODE_NOT_FOUND'
            )
        );
    }

    /**
     * Получает список групп пользователей
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param array $filter
     *
     * @return array
     */
    public function exportGroups($filter = [])
    {
        $items = $this->getGroups($filter);
        $exports = [];
        foreach ($items as $item) {
            if (!empty($item['STRING_ID'])) {
                $exports[] = $this->prepareExportGroup($item);
            }
        }
        return $exports;
    }

    /**
     * Получает код группы пользователей по id
     *
     * @param int|string  $id
     * @param bool|string $default
     *
     * @return bool|string
     */
    public function getGroupCode($id, $default = false)
    {
        $group = $this->getGroup($id);
        return ($group) ? $group['STRING_ID'] : $default;
    }

    /**
     * Получает id группы пользователей по id
     *
     * @param int|string  $code
     * @param bool|string $default
     *
     * @return bool|string
     */
    public function getGroupId($code, $default = false)
    {
        $group = $this->getGroup($code);
        return ($group) ? $group['ID'] : $default;
    }

    /**
     * Получает группу пользователей
     *
     * @param $code int|string - id или код группы
     *
     * @return array|bool
     */
    public function getGroup($code)
    {
        $groupId = is_numeric($code) ? $code : CGroup::GetIDByCode($code);

        if (empty($groupId)) {
            return false;
        }

        /* extract SECURITY_POLICY */
        $item = CGroup::GetByID($groupId)->Fetch();
        if (empty($item)) {
            return false;
        }

        if (!empty($item['SECURITY_POLICY'])) {
            $item['SECURITY_POLICY'] = unserialize($item['SECURITY_POLICY']);
        }

        if ($item['ID'] == self::ADMIN_GROUP_ID) {
            $item['STRING_ID'] = 'administrators';
        } elseif ($item['ID'] == self::ALL_USERS_GROUP_ID) {
            $item['STRING_ID'] = 'everyone';
        }

        return $item;
    }

    protected function prepareExportGroup($item)
    {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['TIMESTAMP_X']);

        return $item;
    }

    protected function prepareFields($fields)
    {
        if (!empty($fields['SECURITY_POLICY']) && is_array($fields['SECURITY_POLICY'])) {
            $fields['SECURITY_POLICY'] = serialize($fields['SECURITY_POLICY']);
        }

        return $fields;
    }
}
