<?php

namespace NaturaSiberica\Api\Repositories\Sale;

use Bitrix\Catalog\Product\CatalogProvider;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketBase;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Order;
use CSaleBasket;
use CSaleUser;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Collections\Error\ErrorCollection;
use NaturaSiberica\Api\DTO\Error\ErrorDTO;
use NaturaSiberica\Api\DTO\Sale\CartDTO;
use NaturaSiberica\Api\ElasticSearch\QueryBuilder;
use NaturaSiberica\Api\Elasticsearch\QueryFilter;
use NaturaSiberica\Api\Elasticsearch\Repositories\ProductsRepository;
use NaturaSiberica\Api\Exceptions\RepositoryException;
use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Factories\Sale\CartFactory;
use NaturaSiberica\Api\Helpers\CityHelper;
use NaturaSiberica\Api\Helpers\Sale\PriceHelper;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Services\Catalog\OfferService;
use NaturaSiberica\Api\V2\Services\Catalog\ProductService;
use NaturaSiberica\Api\Settings;
use NaturaSiberica\Api\Tools\Settings\Options;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;
use NaturaSiberica\Api\Validators\ResultValidator;
use NaturaSiberica\Api\Validators\User\UserValidator;

Loc::loadMessages(__FILE__);

Loader::includeModule('sale');

class CartRepository implements ConstantEntityInterface
{
    use InfoBlockTrait, HighloadBlockTrait;

    /**
     * Максимально доступное количество товара в корзине
     *
     * @var int
     */
    const MAX_QUANTITY = 10;

    private ProductsRepository $productsRepository;
    private QueryBuilder       $builder;

    private ProductService $productService;
    private OfferService   $offerService;

    private ResultValidator $resultValidator;

    /**
     * @var Basket|BasketBase|null
     */
    private ?BasketBase $basket  = null;
    private ?Order      $order   = null;
    private ?CartDTO    $cartDTO = null;

    private ?int $fuserId = null;
    private ?int $cityId  = null;

    public function __construct()
    {
        $this->productsRepository = new ProductsRepository();
        $this->builder            = new QueryBuilder();
        $this->productService     = new ProductService();
        $this->offerService       = new OfferService();
        $this->resultValidator    = new ResultValidator();
    }

    /**
     * Проверка товара на доступность
     *
     * @param int|string $id
     *
     * @return bool
     *
     */
    public static function checkProduct($id): bool
    {
        $instance = new static();
        return $instance->checkElement($id, self::IBLOCK_CATALOG);
    }

    /**
     * @return QueryBuilder
     */
    public function getBuilder(): QueryBuilder
    {
        return $this->builder;
    }

    /**
     * @return Order|null
     */
    public function getOrder(): ?Order
    {
        return $this->order;
    }

    /**
     * @param Order|null $order
     *
     * @return CartRepository
     */
    public function setOrder(?Order $order): CartRepository
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getFuserId(): ?int
    {
        return $this->fuserId;
    }

    /**
     * @param int|null $fuserId
     *
     * @return CartRepository
     */
    public function setFuserId(?int $fuserId): CartRepository
    {
        $this->fuserId = $fuserId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCityId(): ?int
    {
        return $this->cityId;
    }

    /**
     * @param int|null $cityId
     *
     * @return CartRepository
     * @throws Exception
     */
    public function setCityId(?int $cityId = null): CartRepository
    {
        $this->cityId = $cityId ? : $this->getAllCitiesCityId();
        return $this;
    }

    /**
     * @return Basket|null
     */
    public function getBasket(): ?Basket
    {
        return $this->basket;
    }

    /**
     * @param Basket|null $basket
     */
    public function setBasket(?Basket $basket): void
    {
        $this->basket = $basket;
    }

    /**
     * @return CartDTO
     * @throws Exception
     */
    public function get(): CartDTO
    {
        RepositoryException::assertNotNull('fuserId', $this->fuserId);
        $this->validateBasket();

        return CartFactory::createDTO($this);
    }

    /**
     * @return CartRepository
     * @throws Exception
     */
    private function validateBasket(): CartRepository
    {
        if ($this->basket === null) {
            throw new Exception(Loc::getMessage('error_cart_is_missing'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }
        return $this;
    }

    /**
     * @param array $productItems
     *
     * @return bool
     *
     * @throws ArgumentException
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws ArgumentTypeException
     * @throws NotImplementedException
     * @throws NotSupportedException
     * @throws ObjectNotFoundException
     * @throws Exception
     */
    public function update(array $productItems): bool
    {
        $this->validateBasket();

        $defaultStoreId = Options::getDefaultStore();

        $this->validateProductItems($productItems, $defaultStoreId);

        foreach ($productItems as $productItem) {
            if ($item = $this->basket->getExistsItem('catalog', $productItem['offerId'])) {
                if ($productItem['quantity'] === 0) {
                    $item->delete();
                } else {
                    $item->setField('QUANTITY', $productItem['quantity']);
                }
            } else {
                if ($productItem['quantity'] === 0) {
                    continue;
                }

                $item = $this->basket->createItem('catalog', $productItem['offerId']);
                $item->setFields([
                    'QUANTITY'               => $productItem['quantity'],
                    'CURRENCY'               => CurrencyManager::getBaseCurrency(),
                    'LID'                    => Context::getCurrent()->getSite(),
                    'PRODUCT_PROVIDER_CLASS' => CatalogProvider::class,
                ]);
            }
        }

        $result = $this->basket->save();

        return $result->isSuccess();
    }

    /**
     * @throws Exception
     */
    private function validateProductItems(array $productItems, int $storeId): bool
    {
        $errorCollection = new ErrorCollection();
        if (empty($productItems)) {
            throw new Exception(Loc::getMessage('error_empty_product_items'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        $errors = [];

        $this->validateProductIds($productItems);

        $ids          = array_column($productItems, 'offerId');
        $names        = $this->getProductItemNames($ids);
        $itemsInStore = $this->findCartItemsInStore($storeId, $ids);

        foreach ($productItems as &$productItem) {
            $productItem['name'] = $names[$productItem['offerId']];

            if (! isset($productItem['offerId'])) {
                throw new Exception(Loc::getMessage('error_product_id_not_defined'), StatusCodeInterface::STATUS_BAD_REQUEST);
            }

            $checkOffer = self::checkOffer($productItem['offerId']);

            if (! $checkOffer) {
                $errorDto = ErrorDTO::createFromParameters(
                    'unexisting_product',
                    StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
                    Loc::getMessage('error_unexisting_products', [
                        '#PRODUCTS#' => $this->prepareErrorMessage($productItem),
                    ])
                );
                $errorCollection->offsetSet(null, $errorDto);
            } elseif ($checkOffer && ! $this->checkQuantity($productItem, $itemsInStore[$storeId])) {
                $errorDto = ErrorDTO::createFromParameters(
                    'error_product_quantity',
                    StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
                    $this->prepareErrorMessage($productItem, $itemsInStore[$storeId])
                );
                $errorCollection->offsetSet(null, $errorDto);
            } elseif ($checkOffer && ! isset($productItem['quantity'])) {
                throw new Exception(
                    Loc::getMessage('error_quantity_not_defined', [
                        '#PRODUCTS#' => $this->prepareErrorMessage($productItem),
                    ]), StatusCodeInterface::STATUS_BAD_REQUEST
                );
            }
        }

        if (! empty($errorCollection->toArray())) {
            throw new Exception($errorCollection->toJson());
        }

        return true;
    }

    private function validateProductIds(array $productItems)
    {
        if (empty(array_column($productItems, 'offerId'))) {
            throw new Exception(Loc::getMessage('error_product_id_not_defined'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }

    private function getProductItemNames(array $productItemsIds): array
    {
        $names = [];
        $items = ElementTable::getList([
            'filter' => [
                'ID' => $productItemsIds,
            ],
            'select' => ['ID', 'NAME'],
        ])->fetchAll();

        foreach ($items as $item) {
            $id         = (int)$item['ID'];
            $name       = $item['NAME'];
            $names[$id] = $name;
        }

        return $names;
    }

    /**
     * @param int   $storeId
     * @param array $productIds
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function findCartItemsInStore(int $storeId, array $productIds): array
    {
        $result = [];
        $params = [
            'filter' => [
                'PRODUCT_ID' => $productIds,
                'STORE_ID'   => $storeId,
            ],
            'group'  => ['PRODUCT_ID'],
        ];

        $data = StoreProductTable::getList($params)->fetchAll();

        foreach ($data as $item) {
            if ((int)$item['STORE_ID'] !== $storeId) {
                continue;
            }

            $result[$storeId][$item['PRODUCT_ID']] = (int)$item['AMOUNT'];
        }

        return $result;
    }

    /**
     * Проверка торгового предложения на доступность.
     *
     * @param $id
     *
     * @return bool
     */
    public static function checkOffer($id): bool
    {
        $instance = new static();
        return $instance->checkElement($id, self::IBLOCK_OFFER);
    }

    private function checkElement($elementId, string $iblockCode): bool
    {
        $element = $this->productsRepository->one($elementId, $iblockCode);
        return is_array($element);
    }

    private function prepareErrorMessage(array $productItem, array $itemsInStore = null): string
    {
        $id = $productItem['offerId'];

        if (! empty($productItem['name'])) {
            $name = sprintf('%s', $productItem['name']);
        } else {
            $name = sprintf('#%s', $id);
        }

        if ($itemsInStore === null) {
            return $name;
        }

        if ($itemsInStore[$id] === 0 || ! isset($itemsInStore[$id])) {
            return Loc::getMessage('error_out_of_stock', [
                '#PRODUCT#' => $name,
            ]);
        }

        return Loc::getMessage('error_less_quantity', [
            '#PRODUCT#'  => $name,
            '#QUANTITY#' => $itemsInStore[$id],
        ]);
    }

    /**
     * Проверка максимального количества добавляемого товара в корзину
     *
     * @param array $productItem
     * @param array $itemsInStore
     *
     * @return bool
     *
     */
    public function checkQuantity(array $productItem, array $itemsInStore): bool
    {
        return $productItem['quantity'] <= $itemsInStore[$productItem['offerId']];
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    public function delete(): bool
    {
        $this->validateFuser();
        return CSaleBasket::DeleteAll($this->fuserId);
    }

    /**
     * @return CartRepository
     * @throws Exception
     */
    private function validateFuser(): CartRepository
    {
        UserValidator::validateFuser($this->fuserId, Loc::getMessage('error_fuser_not_defined'));
        return $this;
    }

    /**
     * @param int $fuserId
     *
     * @return bool
     *
     * @throws Exception
     */
    public function check(int $fuserId): bool
    {
        if (empty($this->basket)) {
            $this->initBasket($fuserId);
        }

        $storeId = Options::getDefaultStore();
        $items   = $this->getCartItems(false, 'offerId');

        return $this->validateProductItems($items, $storeId);
    }

    /**
     * @param int $fuserId
     *
     * @return $this
     *
     * @throws Exception
     */
    public function initBasket(int $fuserId): CartRepository
    {
        $this->setFuserId($fuserId);

        /**
         * @var Basket $basket
         */
        $basket = $this->checkForFuser() ? $this->getByFuser() : $this->create();

        $this->setBasket($basket);

        return $this;
    }

    protected function checkForFuser(): bool
    {
        $basket = BasketTable::getList([
            'filter' => ['FUSER_ID' => $this->fuserId],
            'select' => ['FUSER_ID'],
        ])->fetch();

        return is_array($basket);
    }

    /**
     * @return BasketBase
     *
     * @throws Exception
     */
    public function getByFuser(): BasketBase
    {
        $this->validateFuser();

        return Basket::loadItemsForFUser($this->fuserId, Context::getCurrent()->getSite());
    }

    /**
     * Создание корзины
     *
     * @return Basket|null
     *
     * @throws Exception
     */
    public function create(): ?Basket
    {
        $this->validateFuser();
        /**
         * @var Basket $basket
         */
        $basket = Basket::create(Context::getCurrent()->getSite());

        if ($this->fuserId !== $basket->getFUserId()) {
            CSaleBasket::TransferBasket($basket->getFUserId(), $this->fuserId);
            CSaleUser::Update($this->fuserId);
        }

        $basket->setFUserId($this->fuserId);
        $save = $basket->save();

        $this->resultValidator->validate($save);

        return $basket;
    }

    /**
     * Список товаров в корзине
     *
     * @param bool   $onlyIds
     * @param string $productIdKey
     *
     * @return array
     *
     * @throws Exception
     */
    public function getCartItems(bool $onlyIds = false, string $productIdKey = 'product_id'): array
    {
        $this->validateFuser();
        $this->validateBasket();

        $result = [];
        $ids    = array_column($this->basket->toArray(), 'PRODUCT_ID');

        if ($onlyIds) {
            return $ids;
        }

        /**
         * @var BasketItem $item
         */
        foreach ($this->basket as $item) {
            $productId = (int)$item->getProductId();
            $result[]  = [
                $productIdKey => $productId,
                'quantity'    => (int)$item->getQuantity(),
            ];
        }

        return $result;
    }

    public function calculateDiscount(array $mindboxResponse, array $productItems)
    {
        $this->validateBasket();

        $lines = $mindboxResponse['order']['lines'];

        foreach ($lines as $line) {
            $mbArticle       = $line['product']['ids']['naturaSiberica'];
            $mbBasePrice     = PriceHelper::format($line['basePricePerItem']);
            $mbDiscountPrice = PriceHelper::format($line['discountedPriceOfLine']);

            foreach ($productItems as $productItem) {
                $productItemId = $productItem['id'];
                if ($mbArticle !== $productItem['article']) {
                    continue;
                }

                /**
                 * @var BasketItem $basketItem
                 */
                foreach ($this->basket->getOrderableItems() as $basketItem) {
                    $basketItemProductId = (int)$basketItem->getProductId();
                    $basketItemBasePrice = PriceHelper::format($basketItem->getBasePrice());

                    if ($productItemId !== $basketItemProductId) {
                        continue;
                    }

                    $basketItem->setFields([
                        'BASE_PRICE'     => $line['basePricePerItem'],
                        'PRICE'          => $line['discountedPriceOfLine'],
                        'CUSTOM_PRICE'   => 'Y',
                        'DISCOUNT_PRICE' => $line['basePricePerItem'] - $line['discountedPriceOfLine'],
                    ]);
                }
            }
        }

        $save = $this->basket->save();
        return $this;
    }

    /**
     * @param array    $cartItems
     * @param int|null $storeId
     *
     * @return bool
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function validateCartItemsInStore(array $cartItems, int $storeId = null): bool
    {
        if (empty($storeId)) {
            $storeId = Settings::getDefaultStoreId();
        }

        $filter = [
            'LOGIC' => 'OR',
        ];

        $query = StoreProductTable::query();
        $query->setSelect(['STORE_ID', 'PRODUCT_ID', 'AMOUNT'])->setGroup(['STORE_ID']);

        foreach ($cartItems as $cartItem) {
            $filter[] = [
                '=PRODUCT_ID' => $cartItem['product_id'],
                '>=AMOUNT'    => $cartItem['quantity'],
            ];
        }

        $query->setFilter([
            '=STORE_ID' => $storeId,
            $filter,
        ]);

        $data = $query->fetchAll();

        if (count($cartItems) > count($data)) {
            throw new Exception(Loc::getMessage('error_missing_basket_items_in_selected_store'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        return true;
    }

    /**
     * @param int   $storeId
     * @param array $cartItems
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getCartItemsInStore(int $storeId, array $cartItems): array
    {
        $cartItemsIds  = array_column($cartItems, 'product_id');
        $data          = $this->findCartItemsInStore($storeId, $cartItemsIds);
        $storeProducts = [];

        if (count($cartItems) !== count($data[$storeId])) {
            return [];
        }

        foreach ($cartItems as $item) {
            $productId = $item['product_id'];
            if ($item['quantity'] > $data[$storeId][$productId]) {
                continue;
            }

            $storeProducts[] = [
                'productId' => $productId,
                'quantity'  => $data[$storeId][$productId],
            ];
        }

        return count($cartItems) === count($storeProducts) ? $storeProducts : [];
    }

    /**
     * Список складов, на которых есть ВСЕ товары из корзины в нужном количестве
     *
     * @param array $cartItems Список товаров в корзине
     * @param int   $cityId
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getAvailableStores(array $cartItems, int $cityId = 0): array
    {
        $results           = [];
        $cartItemsInStores = $this->getCartItemsInStores($cartItems, $cityId);

        foreach ($cartItemsInStores as $storeId => $itemsInStore) {
            if (count($cartItems) !== count($itemsInStore['items'])) {
                continue;
            }

            $results[$storeId] = $itemsInStore;
        }

        return $results;
    }

    /**
     * Список товаров из корзины на складах конкретного города
     *
     * @param array $cartItems Список товаров в корзине
     * @param int   $cityId    ID города
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getCartItemsInStores(array $cartItems, int $cityId = 0): array
    {
        if ($cityId === 0) {
            $cityId = $this->getAllCitiesCityId();
        }

        $results    = [];
        $productIds = array_column($cartItems, 'product_id');

        $this->prepareQueryBuilder($cityId, self::IBLOCK_OFFER, $productIds);

        $data      = $this->builder->exec();
        $cityIdKey = $this->getCityIdKey($cityId);

        $src = array_column($data['hits']['hits'], '_source');

        foreach ($src as $item) {
            $productId = $item['id'];

            if (! array_key_exists($cityIdKey, $item['shops'])) {
                continue;
            }

            $shops = $item['shops'][$cityIdKey];

            array_filter($shops, function ($shop) use ($productId, $cityId, $cartItems, &$results) {
                $storeId  = $shop['id'];
                $quantity = (int)$shop['quantity'];

                $results[$storeId]['city_id']      = $cityId;
                $results[$storeId]['is_warehouse'] = $shop['is_warehouse'];
                $results[$storeId]['is_shop']      = $shop['is_shop'];

                foreach ($cartItems as $cartItem) {
                    if ($productId === $cartItem['product_id'] && $quantity >= $cartItem['quantity']) {
                        $results[$storeId]['items'][] = [
                            'product_id' => $productId,
                            'quantity'   => $quantity,
                        ];
                    }
                }
            });
        }

        return $results;
    }

    /**
     * @return int
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getAllCitiesCityId(): int
    {
        $query = $this->getHlEntityByEntityName(self::HLBLOCK_CITY)->getDataClass()::query();

        $query->addFilter('UF_NAME', 'Все города')->setSelect(['ID']);

        $data = $query->fetchObject();

        return $data !== null ? $data->getId() : 0;
    }

    /**
     * @param int    $cityId
     * @param string $iblockCode
     * @param array  $productIds
     *
     * @return CartRepository
     */
    private function prepareQueryBuilder(int $cityId, string $iblockCode, array $productIds): CartRepository
    {
        $params = [
            'ids' => $productIds,
        ];
        $json   = json_encode($params);

        $queryFilter = new QueryFilter($json);

        // TODO: Пока фильтрация по городу не используется, в будущем подключим
        /**
         * $filter      = [
         * ['term' => ['city_id_list' => $this->getAllCitiesCityId()]],
         * ];
         *
         * $queryFilter->setFilter($filter);
         */
        $this->builder->setIndex($iblockCode)->setFilter($queryFilter->exec());

        return $this;
    }

    /**
     * @param int $cityId
     *
     * @return string
     */
    private function getCityIdKey(int $cityId): string
    {
        return sprintf('city_%d', $cityId);
    }

    /**
     * @param array $offersIds
     * @param int   $cityId
     *
     * @return array
     *
     * @throws RequestBodyException
     */
    public function getProductOffers(array $offersIds, int $cityId = 0): array
    {
        $this->setCityId($cityId);

        $results = [];

        $productsIds = $this->offerService->index([
            'city'   => CityHelper::getCityId(),
            'filter' => $this->prepareIdsForFilter($offersIds),
        ], 'show_list', true);

        $products = $this->productService->index([
            'city'    => CityHelper::getCityId(),
            'filter'  => $this->prepareIdsForFilter($productsIds),
            'is_cart' => true,
        ]);

        $list = $products['list'];

        foreach ($offersIds as $offersId) {
            $this->prepareProductOffers($offersId, $list, $results);
        }

        return $results;
    }

    /**
     * @param array $ids
     *
     * @return false|string
     */
    private function prepareIdsForFilter(array $ids)
    {
        return json_encode([
            'ids' => $ids,
        ]);
    }

    /**
     * @param int   $offerId
     * @param array $productList
     *
     * @param array $result
     *
     * @return CartRepository
     */
    private function prepareProductOffers(int $offerId, array $productList, array &$result): CartRepository
    {
        foreach ($productList as &$productItem) {
            $offerList = &$productItem['offerList'];
            $offerIds  = array_column($offerList, 'id');
            array_filter($offerList, function (&$item) use ($offerId, &$productItem, $offerIds, &$result) {
                $productItem['selectedOffer'] = $offerId;

                if (in_array($offerId, $offerIds)) {
                    $result[$offerId] = $productItem;
                }
            });
        }

        return $this;
    }
}
