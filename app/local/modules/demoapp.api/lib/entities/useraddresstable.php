<?php

namespace NaturaSiberica\Api\Entities;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Exception;
use NaturaSiberica\Api\Validators\ResultValidator;

Loc::loadMessages(__FILE__);

class UserAddressTable extends DataManager
{
    private static bool $isHandlerDisallow = false;

    public static function getTableName(): string
    {
        return 'ns_api_user_address';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('id'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new IntegerField('userId'))
                ->configureRequired(),
            (new StringField('fiasId'))
                ->configureRequired(),
            (new StringField('name')),
            (new TextField('fullAddress'))
                ->configureNullable(),
            (new StringField('region'))
                ->configureRequired(),
            (new StringField('city'))
                ->configureRequired(),
            (new StringField('street'))
                ->configureRequired(),
            (new StringField('houseNumber'))
                ->configureRequired(),
            (new StringField('flat'))
                ->configureNullable(),
            (new StringField('entrance'))
                ->configureNullable(),
            (new StringField('floor'))
                ->configureNullable(),
            (new StringField('doorPhone'))
                ->configureNullable(),
            (new StringField('latitude'))
                ->configureRequired(),
            (new StringField('longitude'))
                ->configureRequired(),
            (new BooleanField('default'))
                ->configureRequired()
                ->configureDefaultValue(false),
            (new BooleanField('privateHouse'))
                ->configureRequired()
                ->configureDefaultValue(false)
        ];
    }

    /**
     * Конвертация адреса в строку для добавления к заказу
     *
     * @param int|null $id
     * @param array    $fields
     *
     * @return string
     * @throws Exception
     */
    public static function toString(int $id = null, array $fields = []): string
    {
        if ($id !== null) {
            $address = self::getById($id)->fetch();
        } elseif (!empty($fields)) {
            $address = $fields;
        }

        $string = sprintf(
            '%s, %s, ул. %s, %s',
            $address['region'], $address['city'], $address['street'], $address['houseNumber']
        );

        if (!empty($address['flat'])) {
            $string .= sprintf(', кв. %s', $address['flat']);
        }

        if (!empty($address['entrance'])) {
            $string .= sprintf(', %s подъезд', $address['entrance']);
        }

        if (!empty($address['floor'])) {
            $string .= sprintf(', %s этаж', $address['floor']);
        }

        if (!empty($address['doorPhone'])) {
            $string .= sprintf(', домофон %s', $address['doorPhone']);
        }

        return $string;
    }

    /**
     * @throws Exception
     */
    protected static function callOnBeforeAddEvent($object, $fields, $result): void
    {
        $userId = $fields['userId'];
        if ($fields['default'] === true) {
            static::resetDefault([
                'filter' => ['=userId' => $userId]
            ]);
        }

        parent::callOnBeforeAddEvent($object, $fields, $result);
    }

    /**
     * @param array $parameters
     *
     * @return bool
     * @throws Exception
     */
    public static function resetDefault(array $parameters = []): bool
    {
        $fields = ['default' => false];

        $ids = self::getIds($parameters);

        $updateResult = new UpdateResult();
        $validator = new ResultValidator();

        foreach ($ids as $id) {
            $result = self::update($id, $fields);

            if (!empty($result->getErrors())) {
                $updateResult->addErrors($result->getErrors());
            }
        }

        $validator->validate($updateResult, 'db_error_on_reset_default_address');

        return $updateResult->isSuccess();
    }

    /**
     * @param $object
     * @param $fields
     * @param $result
     *
     * @return void
     *
     * @throws Exception
     */
    protected static function callOnBeforeUpdateEvent($object, $fields, $result): void
    {
        /**
         * @var EntityObject $object
         * @var UpdateResult $result
         */

        if (self::$isHandlerDisallow) {
            parent::callOnBeforeUpdateEvent($object, $fields, $result);
            return;
        }

        self::$isHandlerDisallow = true;

        if ($fields['default'] === true) {

            $userId = self::getByPrimary($object->getId(), [
                'select' => ['userId']
            ])->fetchObject();

            if ($userId !== null) {
                $parameters = [
                    'filter' => [
                        '!id' => $object->getId(),
                        '=userId' => $userId->get('userId')
                    ]
                ];

                self::resetDefault($parameters);
            }


        }

        self::$isHandlerDisallow = false;

        parent::callOnBeforeUpdateEvent($object, $fields, $result);
    }

    /**
     * @param array $parameters
     *
     * @return array
     *
     * @throws Exception
     */
    public static function getIds(array $parameters = []): array
    {
        $select = ['id'];
        $parameters['select'] = !empty($parameters['select']) ? array_merge($parameters['select'], $select) : $select;

        $ids = [];

        $list = self::getList($parameters)->fetchAll();

        foreach ($list as $item) {
            $ids[] = (int) $item['id'];
        }

        return $ids;
    }
}
