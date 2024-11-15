<?php

namespace Userstory\I18n\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use DateTime;

/**
 * Class IblockMigrationTable
 * 
 * @package Userstory\I18n\Orm
 */
class IblockMigrationTable extends DataManager
{
    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'us_i18n_iblock_migrations';
    }

    /**
     * @return array
     */
    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new StringField('LID_FROM'))
                ->configureRequired()
                ->addValidator(new LengthValidator(1, 2)),
            (new IntegerField('IBLOCK_ID_FROM'))
                ->configureRequired(),
            (new StringField('LID_TO'))
                ->configureRequired()
                ->addValidator(new LengthValidator(1, 2)),
            (new IntegerField('IBLOCK_ID_TO'))
                ->configureNullable(),
            (new IntegerField('STATUS'))
                ->configureRequired(),
            (new DatetimeField('CREATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(new DateTime()),
            (new DatetimeField('UPDATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(new DateTime())
        ];
    }
}
