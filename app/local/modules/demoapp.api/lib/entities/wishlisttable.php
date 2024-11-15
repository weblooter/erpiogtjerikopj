<?php

namespace NaturaSiberica\Api\Entities;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Repositories\Sale\CartRepository;

Loc::loadMessages(__FILE__);

class WishListTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'ns_api_wishlist';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('productId'))->configurePrimary(),
            (new IntegerField('userId'))->configurePrimary(),
            (new DatetimeField('dateInsert'))->configureDefaultValue(new DateTime()),
        ];
    }

    /**
     * Получение всех записей с фильтрацией по пользователю
     *
     * @param int $userId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getByUserId(int $userId): array
    {
        $wishList = [
            'userId' => $userId,
            'items'  => [],
        ];

        $items = self::getList([
            'order' => ['dateInsert' => 'desc'],
            'filter' => ['userId' => $userId],
            'select' => ['productId', 'userId'],
        ]);

        foreach ($items->fetchAll() as $item) {
            $wishList['items'][] = (int)$item['productId'];
        }

        return $wishList;
    }

    /**
     * Получение всех записей с фильтрацией по товару
     *
     * @param int $productId
     *
     * @return array|null
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getByProductId(int $productId): ?array
    {
        return self::getList([
            'filter' => ['=productId' => $productId],
        ])->fetchAll();
    }

    /**
     * Удаление записей с фильтрацией по товару
     *
     * @param int $productId
     *
     * @return void
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function deleteByProductId(int $productId)
    {
        $rows = self::getByProductId($productId);

        if (empty($rows)) {
            return;
        }

        foreach ($rows as &$row) {
            unset($row['dateInsert']);
            self::delete($row);
        }
    }

    protected static function callOnBeforeAddEvent($object, $fields, $result)
    {
        /**
         * @var AddResult $result
         */

        if (static::getByPrimary($fields)->fetch() !== false) {
            $result->addError(
                new Error(
                    Loc::getMessage('error_duplicate_product', [
                        '#PRODUCT_ID#' => $fields['productId'],
                    ]), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
                )
            );
        } elseif (! CartRepository::checkProduct($fields['productId'])) {
            $result->addError(
                new Error(
                    Loc::getMessage('error_products_not_exists', [
                        '#PRODUCT_ID#' => $fields['productId'],
                    ]), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
                )
            );
        }

        parent::callOnBeforeAddEvent($object, $fields, $result);
    }

    protected static function callOnBeforeDeleteEvent($object, $entity, $result)
    {
        /**
         * @var EntityObject $object
         * @var Entity       $entity
         * @var DeleteResult $result
         */

        $row = static::getByPrimary([
            'productId' => $object['productId'],
            'userId'    => $object['userId'],
        ])->fetch();

        if (! $row) {
            $result->addError(
                new Error(
                    Loc::getMessage('error_product_id_not_found', [
                        '#PRODUCT_ID#' => $object['productId'],
                    ]), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
                )
            );
        }

        if (! CartRepository::checkProduct($object['productId'])) {
            $result->addError(
                new Error(
                    Loc::getMessage('error_products_not_exists', [
                        '#PRODUCT_ID#' => $object['productId'],
                    ]), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
                )
            );
        }

        parent::callOnBeforeDeleteEvent($object, $entity, $result);
    }
}
