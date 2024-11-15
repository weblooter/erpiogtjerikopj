<?php

namespace NaturaSiberica\Api\Entities;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Bitrix\Sale\FuserTable;

class TokensTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'ns_api_tokens';
    }

    /**
     * @throws SystemException
     * @throws ArgumentException
     */
    public static function getMap(): array
    {
        return [
            (new IntegerField('FUSER_ID'))
                ->configurePrimary(),
            (new IntegerField('USER_ID'))
                ->configureNullable()
                ->configureUnique(),
            (new StringField('ACCESS_TOKEN'))
                ->configureRequired(),
            (new StringField('REFRESH_TOKEN'))
                ->configureRequired(),
            (new DatetimeField('CREATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(new DateTime()),
            (new DatetimeField('UPDATED_AT'))
                ->configureRequired()
                ->configureDefaultValue(new DateTime()),
            (new DatetimeField('ACCESS_TOKEN_EXPIRES_AT'))
                ->configureRequired(),
            (new DatetimeField('REFRESH_TOKEN_EXPIRES_AT'))
                ->configureRequired(),
            new Reference(
                'FUSER',
                FuserTable::class,
                Join::on('this.FUSER_ID', 'ref.ID')
            ),
            new Reference(
                'USER',
                UserTable::class,
                Join::on('this.USER_ID', 'ref.ID')
            )
        ];
    }
}
