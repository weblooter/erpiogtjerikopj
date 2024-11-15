<?php

namespace NaturaSiberica\Api\Entities\Tokens;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Type\DateTime;

class TokensTable extends DataManager
{
    public static function getTableName()
    {
        return 'ns_api_tokens';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('id'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new IntegerField('fuserId'))
                ->configureRequired(),
            (new StringField('refreshToken'))
                ->configureRequired()
                ->configureUnique(),
            (new DatetimeField('created'))
                ->configureDefaultValue(new DateTime()),
            (new DatetimeField('expires'))
                ->configureRequired()
        ];
    }
}
