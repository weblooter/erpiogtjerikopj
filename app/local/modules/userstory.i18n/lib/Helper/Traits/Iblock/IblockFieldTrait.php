<?php

namespace Userstory\I18n\Helper\Traits\Iblock;

use CIBlock;

trait IblockFieldTrait
{
    /**
     * Получает список полей инфоблока
     *
     * @param $iblockId
     *
     * @return array|bool
     */
    public function getIblockFields($iblockId)
    {
        return CIBlock::GetFields($iblockId);
    }

    /**
     * Получает список полей инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param $iblockId
     *
     * @return array
     */
    public function exportIblockFields($iblockId)
    {
        return $this->prepareExportIblockFields(
            $this->getIblockFields($iblockId)
        );
    }

    /**
     * Обновляет поля инфоблока
     *
     * @param $iblockId
     * @param $fields
     *
     * @return bool
     */
    public function updateIblockFields($iblockId, $fields)
    {
        if ($iblockId && !empty($fields)) {
            CIBlock::SetFields($iblockId, $fields);
            return true;
        }
        return false;
    }

    /**
     * @param $iblockId
     * @param $fields
     *
     * @deprecated
     */
    public function mergeIblockFields($iblockId, $fields)
    {
        $this->saveIblockFields($iblockId, $fields);
    }

    public function exportIblockElementFields($iblockId)
    {
        return $this->prepareExportIblockElementFields(
            $this->getIblockFields($iblockId)
        );
    }

    protected function prepareExportIblockFields($fields)
    {
        if (empty($fields)) {
            return $fields;
        }

        $exportFields = [];
        foreach ($fields as $code => $field) {
            if ($field['VISIBLE'] == 'N' || preg_match('/^(LOG_)/', $code)) {
                continue;
            }
            $exportFields[$code] = $field;
        }

        return $exportFields;
    }

    protected function prepareExportIblockElementFields($fields)
    {
        if (empty($fields)) {
            return $fields;
        }

        $exportFields = [];
        foreach ($fields as $code => $field) {
            if ($field['VISIBLE'] == 'N' || preg_match('/^(SECTION_|LOG_)/', $code)) {
                continue;
            }
            $exportFields[$code] = $field;
        }

        return $exportFields;
    }
}
