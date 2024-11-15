<?php

namespace NaturaSiberica\Api\Entities;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\UserTable;
use Bitrix\Sale\FuserTable;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Repositories\Sale\CartRepository;

class FavouriteTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'ns_favorite';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new IntegerField('PRODUCT_ID'))
                ->configureRequired(),
            (new IntegerField('USER_ID'))
                ->configureNullable()
                ->configureDefaultValue(null),
            (new IntegerField('FUSER_ID'))
                ->configureRequired(),
            (new DatetimeField('DATE_INSERT'))
                ->configureDefaultValue(new DateTime()),
            new Reference(
                'PRODUCT',
                ElementTable::class,
                Join::on('this.PRODUCT_ID', 'ref.ID')
            ),
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
