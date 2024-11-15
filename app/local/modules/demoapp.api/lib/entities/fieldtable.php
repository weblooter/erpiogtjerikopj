<?php

namespace NaturaSiberica\Api\Entities;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

class FieldTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_user_field';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('FIELD_ENTITY_ID_FIELD')
                ]
            ),
            new StringField(
                'ENTITY_ID',
                [
                    'validation' => [__CLASS__, 'validateEntityId'],
                    'title' => Loc::getMessage('FIELD_ENTITY_ENTITY_ID_FIELD')
                ]
            ),
            new StringField(
                'FIELD_NAME',
                [
                    'validation' => [__CLASS__, 'validateFieldName'],
                    'title' => Loc::getMessage('FIELD_ENTITY_FIELD_NAME_FIELD')
                ]
            ),
            new StringField(
                'USER_TYPE_ID',
                [
                    'validation' => [__CLASS__, 'validateUserTypeId'],
                    'title' => Loc::getMessage('FIELD_ENTITY_USER_TYPE_ID_FIELD')
                ]
            ),
            new StringField(
                'XML_ID',
                [
                    'validation' => [__CLASS__, 'validateXmlId'],
                    'title' => Loc::getMessage('FIELD_ENTITY_XML_ID_FIELD')
                ]
            ),
            new IntegerField(
                'SORT',
                [
                    'title' => Loc::getMessage('FIELD_ENTITY_SORT_FIELD')
                ]
            ),
            new BooleanField(
                'MULTIPLE',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                    'title' => Loc::getMessage('FIELD_ENTITY_MULTIPLE_FIELD')
                ]
            ),
            new BooleanField(
                'MANDATORY',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                    'title' => Loc::getMessage('FIELD_ENTITY_MANDATORY_FIELD')
                ]
            ),
            new BooleanField(
                'SHOW_FILTER',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                    'title' => Loc::getMessage('FIELD_ENTITY_SHOW_FILTER_FIELD')
                ]
            ),
            new BooleanField(
                'SHOW_IN_LIST',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'Y',
                    'title' => Loc::getMessage('FIELD_ENTITY_SHOW_IN_LIST_FIELD')
                ]
            ),
            new BooleanField(
                'EDIT_IN_LIST',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'Y',
                    'title' => Loc::getMessage('FIELD_ENTITY_EDIT_IN_LIST_FIELD')
                ]
            ),
            new BooleanField(
                'IS_SEARCHABLE',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                    'title' => Loc::getMessage('FIELD_ENTITY_IS_SEARCHABLE_FIELD')
                ]
            ),
            new TextField(
                'SETTINGS',
                [
                    'title' => Loc::getMessage('FIELD_ENTITY_SETTINGS_FIELD')
                ]
            ),
        ];
    }

    /**
     * Returns validators for ENTITY_ID field.
     *
     * @return array
     */
    public static function validateEntityId()
    {
        return [
            new LengthValidator(null, 50),
        ];
    }

    /**
     * Returns validators for FIELD_NAME field.
     *
     * @return array
     */
    public static function validateFieldName()
    {
        return [
            new LengthValidator(null, 50),
        ];
    }

    /**
     * Returns validators for USER_TYPE_ID field.
     *
     * @return array
     */
    public static function validateUserTypeId()
    {
        return [
            new LengthValidator(null, 50),
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
