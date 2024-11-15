<?php

namespace NaturaSiberica\Api\Entities;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

class FieldEnumTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'b_user_field_enum';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     *
     * @throws SystemException
     */
    public static function getMap(): array
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_ID_FIELD')
                ]
            ),
            new IntegerField(
                'USER_FIELD_ID',
                [
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_USER_FIELD_ID_FIELD')
                ]
            ),
            new StringField(
                'VALUE',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateValue'],
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_VALUE_FIELD')
                ]
            ),
            new BooleanField(
                'DEF',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_DEF_FIELD')
                ]
            ),
            new IntegerField(
                'SORT',
                [
                    'default' => 500,
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_SORT_FIELD')
                ]
            ),
            new StringField(
                'XML_ID',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateXmlId'],
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_XML_ID_FIELD')
                ]
            ),
        ];
    }

    /**
     * Returns validators for VALUE field.
     *
     * @return array
     * @throws ArgumentTypeException
     */
    public static function validateValue()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }

    /**
     * Returns validators for XML_ID field.
     *
     * @return array
     */
    public static function validateXmlId()
    {
        return [
            new LengthValidator(null, 255),
        ];
    }
}
