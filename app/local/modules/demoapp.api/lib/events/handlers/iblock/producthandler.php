<?php

namespace NaturaSiberica\Api\Events\Handlers\Iblock;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\EO_Element;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Internals\BasketTable;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Entities\WishListTable;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

Loader::includeModule('sale');

/**
 * Обработчик событий, связанных с товарами и торговыми предложениями
 *
 * Class ProductHandler
 *
 * @package NaturaSiberica\Api\Events\Handlers\Iblock
 */
class ProductHandler implements ConstantEntityInterface
{
    use InfoBlockTrait;

    private int $productId;
    private int $iblockId;

    /**
     * @var DataManager|\Bitrix\Main\ORM\DataManager|string
     */
    private $product;

    /**
     * @var DataManager|\Bitrix\Main\ORM\DataManager|string
     */
    private $offer;

    /**
     * @param int $iblockId
     * @param int $productId
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function __construct(int $iblockId, int $productId)
    {
        $this->iblockId = $iblockId;
        $this->productId = $productId;

        $this->product = $this->getEntity(self::IBLOCK_CATALOG)->getDataClass();
        $this->offer = $this->getEntity(self::IBLOCK_OFFER)->getDataClass();
    }

    public function isProductIblock(): bool
    {
        return $this->iblockId === $this->getIblockId(self::IBLOCK_CATALOG) || $this->iblockId === $this->getIblockId(self::IBLOCK_OFFER);
    }

    public function checkProductActivity(): bool
    {
        return $this->getElement()->getActive();
    }

    public function checkProductActivityDates(): bool
    {
        $product = $this->getElement();

        if (
            (!$product->hasActiveFrom() && !$product->hasActiveTo() && $product->hasActive())
        ) {
            return true;
        }

        if (
            ($product->hasActiveFrom() && ($product->getActiveFrom() instanceof DateTime) && $product->getActiveFrom()->getTimestamp() >= time()) &&
            (!$product->hasActiveTo() || ($product->getActiveTo() instanceof DateTime && $product->getActiveTo()->getTimestamp() <= time()))
        ) {
            return true;
        }

        if (!$product->hasActiveFrom() && ($product->hasActiveTo() && $product->getActiveTo()->getTimestamp() <= time())) {
            return true;
        }

        return false;
    }

    public function handle()
    {
        $ids = $this->getBasketItemsIds();

        if (empty($ids)) {
            return;
        }

        $this->deleteBasketItems($ids);
        WishListTable::deleteByProductId($this->productId);
    }

    private function getBasketItemsIds(): array
    {
        $ids = [];

        $options = [
            'select' => ['PRODUCT_ID'],
            'filter' => ['=ORDER_ID' => null]
        ];

        switch ($this->iblockId) {
            case $this->getIblockId(self::IBLOCK_CATALOG):
                $options['runtime'] = [
                    new Reference('E', $this->offer, Join::on('this.PRODUCT_ID', 'ref.ID')),
                ];

                $options['filter'] = ['E.CML2_LINK.VALUE' => $this->productId];
                break;
            case $this->getIblockId(self::IBLOCK_OFFER):
                $options['filter'] = ['PRODUCT_ID' => $this->productId];
                break;
        }

        $items = BasketTable::getList($options)->fetchAll();

        foreach ($items as $item) {
            $ids[] = (int) $item['PRODUCT_ID'];
        }

        return $ids;
    }

    private function getElement(): EO_Element
    {
        return ElementTable::getByPrimary(
            $this->productId,
            ['select' => ['ACTIVE', 'ACTIVE_FROM', 'ACTIVE_TO']]
        )->fetchObject();
    }

    private function deleteBasketItems(array $ids): void
    {
        $errors = [];
        foreach ($ids as $id) {
            $delete = BasketTable::deleteWithItems($id);

            if (!empty($delete->getErrorMessages())) {
                $errors[] = $delete->getErrorMessages();
            }
        }

        if (!empty($errors)) {
            throw new Exception(
                implode('. ', $errors),
                StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
            );
        }
    }
}
