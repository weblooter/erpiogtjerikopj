<?php

namespace NaturaSiberica\Api\Services\Mindbox\Sale;

use Bitrix\Main\IO\File;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Sale\Internals\BasketTable;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use Mindbox\DTO\DTO;
use Mindbox\DTO\V3\Responses\OrderResponseDTO;
use Mindbox\DTO\V3\Responses\ProductListItemResponseDTO;
use Mindbox\Exceptions\MindboxUnavailableException;
use Mindbox\Helper;
use Mindbox\MindboxResponse;
use Mindbox\Transaction;
use NaturaSiberica\Api\Helpers\Sale\PriceHelper;
use NaturaSiberica\Api\Interfaces\Services\Mindbox\Sale\OrderServiceInterface;
use NaturaSiberica\Api\Mindbox\MindboxRepository;
use NaturaSiberica\Api\Repositories\Sale\CartRepository;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Services\Mindbox\MindboxService;

Loader::includeModule('mindbox.marketing');
Loc::loadMessages(__FILE__);

class OrderService extends MindboxService implements OrderServiceInterface
{
    private ?CartRepository   $cartRepository = null;
    private UserRepository    $userRepository;
    private MindboxRepository $mindboxRepository;
    private Transaction       $transaction;

    private ?string $orderTransactionId = null;

    public function __construct()
    {
        $this->userRepository    = new UserRepository();
        $this->mindboxRepository = new MindboxRepository();
        $this->transaction       = new Transaction();
        $this->prepareTransactionId();

        parent::__construct();
    }

    /**
     * @param int|null $orderId
     *
     * @return void
     */
    public function prepareTransactionId(int $orderId = null): void
    {
        if ($orderId) {
            $this->transaction = Transaction::getInstance($orderId);
        }

        $this->orderTransactionId = $this->transaction->get();
    }

    /**
     * @return CartRepository|null
     */
    public function getCartRepository(): ?CartRepository
    {
        return $this->cartRepository;
    }

    /**
     * @param CartRepository|null $cartRepository
     */
    public function setCartRepository(?CartRepository &$cartRepository = null): void
    {
        $this->cartRepository = &$cartRepository;
    }

    /**
     * @return Transaction
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    /**
     * @param int $orderId
     *
     * @return void
     */
    public function setTransactionId(int $orderId): void
    {
        $transactionId = $this->transaction->get();
        $this->transaction->addTransaction($orderId, $transactionId);
    }

    /**
     * @return string|null
     */
    public function getOrderTransactionId(): ?string
    {
        return $this->orderTransactionId;
    }

    /**
     * @param int   $userId
     * @param array $requestBody
     * @param bool  $rawResponse
     *
     * @return OrderResponseDTO|MindboxResponse|mixed
     *
     * @throws Exception
     */
    public function calculateAuthorizedCart(int $userId, array $requestBody, bool $rawResponse = false)
    {
        if ($this->cartRepository === null) {
            throw new Exception(Loc::getMessage('err_cart_not_found'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $this->prepareCartCalculationRequestBody($userId, $requestBody);
        $this->prepareDto();

        $this->mindboxClient->prepareRequest('POST', $this->getOperation('calculateAuthorizedCart'), $this->dto);
        $response = $this->getResponse($this->mindboxClient);

        return $rawResponse ? $response : Helper::iconvDTO($response->getResult())->getField('order');
    }

    /**
     * @param int   $userId
     * @param array $requestBody
     *
     * @return void
     * @throws Exception
     */
    protected function prepareCartCalculationRequestBody(int $userId, array $requestBody): void
    {
        if ($this->cartRepository->getBasket() === null) {
            throw new Exception(Loc::getMessage('err_cart_not_found'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $this->resetRequestBody();

        $userDto = $this->userRepository->findById($userId)->get();

        $this->addCustomerToRequestBody($userDto);

        $orderBody = $this->prepareProductItems($requestBody['productItems']);

        if ($requestBody['bonuses']['use']) {
            $bonusPoint = [
                'amount' => $requestBody['bonuses']['amount'],
            ];

            if ($requestBody['bonuses']['systemName']) {
                $bonusPoint['balanceType']['ids']['systemName'] = $requestBody['bonuses']['systemName'];
            }

            $orderBody['bonusPoints'][] = $bonusPoint;
        }
        if($requestBody['coupon']) {
            $orderBody['coupons'][] = ['ids' => ['code' => $requestBody['coupon']]];
        }

        if($requestBody['delivery']) {
            $orderBody['deliveryCost'] = $requestBody['delivery']['price'];
            $orderBody['customFields']['deliveryCity'] = $requestBody['cityId'];
        }

        $this->addDataToRequestBody('order', $orderBody);
    }

    /**
     * @param array $bodyProductItems
     *
     * @return array
     */
    protected function prepareProductItems(array $bodyProductItems): array
    {
        $order = [
            'lines' => [],
        ];

        foreach ($bodyProductItems as $item) {
            $order['lines'][] = [
                'basePricePerItem' => PriceHelper::formatToFloat($item['basePrice']),
                'quantity'         => $item['quantity'],
                'product'          => [
                    'ids' => [
                        'naturaSiberica' => $item['article'],
                    ],
                ],
                'status'           => [
                    'ids' => [
                        'externalId' => 'N',
                    ],
                ],
            ];
        }

        return $order;
    }

    /**
     * @param int  $userId
     * @param bool $rawResponse
     *
     * @return MindboxResponse|ProductListItemResponseDTO|mixed
     *
     * @throws Exception
     */
    public function calculatePriceProduct(int $userId, bool $rawResponse = false)
    {
        if ($this->cartRepository === null) {
            throw new Exception(Loc::getMessage('err_cart_not_found'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $this->prepareProductPriceCalculationRequestBody($userId);
        $this->prepareDto();

        $this->mindboxClient->prepareRequest('POST', $this->getOperation('calculatePriceProduct'), $this->dto);
        $response    = $this->getResponse($this->mindboxClient);
        $productList = Helper::iconvDTO($response->getResult())->getField('productList');

        return $rawResponse ? $response : $productList[1];
    }

    protected function prepareProductPriceCalculationRequestBody(int $userId): void
    {
        if ($this->cartRepository->getBasket() === null) {
            throw new Exception(Loc::getMessage('err_cart_not_found'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $this->resetRequestBody();

        $userDto      = $this->userRepository->findById($userId)->get();
        $cartItemsIds = $this->cartRepository->getCartItems(true);

        $this->addCustomerToRequestBody($userDto);
        $this->addProductListToRequestBody($userDto->fuserId, $cartItemsIds);
    }

    /**
     * @param int   $fuserId
     * @param array $cartItemsIds
     *
     * @return void
     */
    protected function addProductListToRequestBody(int $fuserId, array $cartItemsIds): void
    {
        $rawProductItems = $this->getRawProductItems($fuserId, $cartItemsIds);
        $productList     = [
            'items' => [],
        ];

        foreach ($rawProductItems as $item) {
            $productList['items'][] = [
                'product'          => [
                    'ids' => [
                        'naturaSiberica' => $item['ARTICLE'],
                    ],
                ],
                'basePricePerItem' => $item['BASE_PRICE'],
            ];
        }

        if (! empty($productList)) {
            $this->addDataToRequestBody('productList', $productList);
        }
    }

    /**
     * @param int   $fuserId
     * @param array $productItemsIds
     *
     * @return array
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getRawProductItems(int $fuserId, array $productItemsIds): array
    {
        return BasketTable::getList([
                'filter'  => [
                    'ORDER_ID'   => null,
                    'FUSER_ID'   => $fuserId,
                    'PRODUCT_ID' => $productItemsIds,
                ],
                'select'  => [
                    'FUSER_ID',
                    'PRODUCT_ID',
                    'QUANTITY',
                    'BASE_PRICE',
                    'ARTICLE' => 'E.ARTICLE.VALUE',
                ],
                'runtime' => [
                    new Reference(
                        'E', $this->cartRepository->getEntity(CartRepository::IBLOCK_OFFER)->getDataClass(), Join::on('this.PRODUCT_ID', 'ref.ID')
                    ),
                ],
            ])->fetchAll();
    }

    /**
     * @param int   $userId
     * @param array $payment
     * @param array $delivery
     * @param array $mbCalculatedCart
     * @param bool  $rawResponse
     *
     * @return DTO|MindboxResponse
     *
     * @throws Exception
     */
    public function startOrderTransaction(int $userId, array $payment, array $requestBody, array $mbCalculatedCart, bool $rawResponse = true)
    {
        $this->prepareStartTransactionRequestBody($userId, $payment, $requestBody, $mbCalculatedCart);
        $this->prepareDto();
        $this->mindboxClient->prepareRequest('POST', $this->getOperation('beginAuthorizedOrderTransaction'), $this->dto);

        $response = $this->getResponse($this->mindboxClient);
        return $rawResponse ? $response : Helper::iconvDTO($response->getResult());
    }

    /**
     * @throws Exception
     */
    protected function prepareStartTransactionRequestBody(int $userId, array $payment, array $requestBody, array $mbCalculatedCart): void
    {
        $userDto = $this->userRepository->findById($userId)->get();

        $this->resetRequestBody();
        $this->addCustomerToRequestBody($userDto);
        $this->addOrderRequestBody($payment, $requestBody, $mbCalculatedCart);
    }

    /**
     * @param array $payment
     * @param array $requestBody
     * @param array $mbCalculatedCart
     *
     * @return void
     * @throws Exception
     */
    protected function addOrderRequestBody(array $payment, array $requestBody, array $mbCalculatedCart): void
    {
        if ($this->orderTransactionId === null) {
            throw new Exception(Loc::getMessage('error_invalid_transaction_id'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $delivery = $requestBody['delivery'];

        $order = [
            'deliveryCost' => $delivery['price'],
            'customFields' => [
                'deliveryCity'    => $delivery['cityId'],
                'deliveryType'    => $delivery['typeCode'],
                'deliveryAddress' => $delivery['address'],
                'deliveryTitle'   => $delivery['name'],
                'orderComment'    => $requestBody['comments'],
            ],
            'transaction'  => [
                'ids' => [
                    'externalId' => $this->orderTransactionId,
                ],
            ],
            'payments'     => [$payment],
        ];

        if($requestBody['coupon']) {
            $order['coupons'][] = [
                'ids' => ['code' => $requestBody['coupon']]
            ];
        }

        foreach ($mbCalculatedCart['order']['lines'] as $mbItem) {
            $order['lines'][] = [
                'lineNumber'          => $mbItem['lineNumber'],
                'product'             => $mbItem['product'],
                'quantity'            => $mbItem['quantity'],
                'basePricePerItem'    => $mbItem['basePricePerItem'],
                'status'              => $mbItem['status'],
                'requestedPromotions' => $mbItem['appliedPromotions'],
            ];
        }

        if ($requestBody['bonuses']['use']) {
            $bonusPoint = [
                'amount' => $requestBody['bonuses']['amount']
            ];

            if (array_key_exists('systemName', $requestBody['bonuses'])) {
                $bonusPoint['balanceType']['ids']['systemName'] = $requestBody['bonuses']['systemName'];
            }

            $order['bonusPoints'][] = $bonusPoint;
        }

        $this->addDataToRequestBody('order', $order);
    }

    public function commitOrderTransaction(array $mindboxOrderIds): MindboxResponse
    {
        $this->resetRequestBody();
        $this->addTransactionIdToRequestBody();
        $this->requestBody['order']['ids'] = $mindboxOrderIds;
        $this->prepareDto();

        $this->mindboxClient->prepareRequest('POST', $this->getOperation('commitOrderTransaction'), $this->dto);
        return $this->getResponse($this->mindboxClient);
    }

    /**
     * @return void
     */
    protected function addTransactionIdToRequestBody(): void
    {
        $transaction = [
            'ids' => [
                'externalId' => $this->orderTransactionId,
            ],
        ];

        if (isset($this->requestBody['order'])) {
            $this->requestBody['order']['transaction'] = $transaction;
            return;
        }

        $this->addDataToRequestBody('order', [
            'transaction' => $transaction,
        ]);
    }

    public function rollbackOrderTransaction(): MindboxResponse
    {
        $this->resetRequestBody();
        $this->addTransactionIdToRequestBody();
        $this->prepareDto();

        $this->mindboxClient->prepareRequest('POST', $this->getOperation('rollbackOrderTransaction'), $this->dto);
        return $this->getResponse($this->mindboxClient);
    }
}
