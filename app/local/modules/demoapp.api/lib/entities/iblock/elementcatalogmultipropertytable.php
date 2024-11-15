<?php

namespace NaturaSiberica\Api\Entities\Iblock;

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;

/**
 * ORM-сущность для таблицы множественных свойств товаров.
 * Необходима для получения дополнительных изображений
 */
class ElementCatalogMultiPropertyTable extends DataManager
{
    public static function getTableName()
    {
        $iblockId = IblockTable::getList([
            'filter' => ['CODE' => ConstantEntityInterface::IBLOCK_CATALOG],
            'select' => ['ID']
        ])->fetchObject()->getId();

        return sprintf('b_iblock_element_prop_m%d', $iblockId);
    }

    public static function getMap(): array
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                ]
            ),
            new IntegerField(
                'IBLOCK_ELEMENT_ID',
                [
                    'required' => true,
                ]
            ),
            new IntegerField(
                'IBLOCK_PROPERTY_ID',
                [
                    'required' => true,
                ]
            ),
            new TextField(
                'VALUE',
                [
                    'required' => true,
                ]
            ),
            new IntegerField(
                'VALUE_ENUM'
            ),
            new FloatField(
                'VALUE_NUM'
            ),
            new StringField(
                'DESCRIPTION',
                [
                    'validation' => [__CLASS__, 'validateDescription'],
                ]
            ),
        ];
    }

    /**
     * Returns validators for DESCRIPTION field.
     *
     * @return array
     * @throws ArgumentTypeException
     */
    public static function validateDescription()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }
}
