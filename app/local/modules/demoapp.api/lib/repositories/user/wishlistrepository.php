<?php

namespace NaturaSiberica\Api\Repositories\User;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\User\WishListDTO;
use NaturaSiberica\Api\Entities\WishListTable;
use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Helpers\CityHelper;
use NaturaSiberica\Api\Services\Catalog\ProductService;
use NaturaSiberica\Api\Validators\ResultValidator;
use Spatie\DataTransferObject\DataTransferObject;

Loc::loadMessages(__FILE__);
Loc::loadMessages(dirname(__DIR__, 2) . '/entities/wishlisttable.php');

class WishListRepository
{
    private ResultValidator $validator;


    public function __construct()
    {
        $this->validator = new ResultValidator();
    }

    /**
     * @param int   $userId
     * @param array $products
     *
     * @return DataTransferObject
     * @throws Exception
     */
    public function add(int $userId, array $products): DataTransferObject
    {
        $this->validateProductsOnEmpty($products);
        $result = WishListTable::addMulti(
            $this->prepareMultiPrimaries($userId, $products)
        );

        $this->validator->validate($result, 'db_error_on_add_favourites');

        return $this->findByUser($userId);
    }

    /**
     * @param int $userId
     * @param int $productId
     *
     * @return DataTransferObject
     *
     * @throws Exception
     */
    public function delete(int $userId, int $productId): DataTransferObject
    {
        $primary = [
            'userId'    => $userId,
            'productId' => $productId,
        ];

        $result = WishListTable::delete($primary);

        $this->validator->validate($result, 'db_error_on_delete_favourites');

        return $this->findByUser($userId);
    }

    /**
     * @param int $userId
     *
     * @return WishListDTO
     *
     * @throws Exception
     */
    public function clear(int $userId): WishListDTO
    {
        $dto  = $this->findByUser($userId);
        $list = array_column($dto->list, 'id');

        foreach ($list as $productId) {
            $this->delete($userId, $productId);
        }

        return $this->findByUser($userId);
    }

    /**
     * @param int $userId
     *
     * @return WishListDTO
     * @throws ArgumentException
     * @throws SystemException
     * @throws ObjectPropertyException
     * @throws RequestBodyException
     */
    public function findByUser(int $userId): DataTransferObject
    {
        $favourites = WishListTable::getByUserId($userId);

        $productService = new ProductService();
        $products       = $productService->index([
            'filter' => json_encode(['ids' => $favourites['items']]),
            'city'   => CityHelper::getCityId(),
        ]);

        return new WishListDTO([
            'userId' => $userId,
            'list'   => $this->sortList(($favourites['items'] ?? []), ($products['list'] ?? [])),
        ]);
    }

    public function sortList(array $favourites, array $products): array
    {
        $result = [];
        if($favourites && $products) {
            foreach ($favourites as $item) {
                foreach ($products as $product) {
                    if($item === $product['id']) {
                        $result[] = $product;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param int   $userId
     * @param array $products
     *
     * @return array
     * @throws Exception
     */
    protected function prepareMultiPrimaries(int $userId, array $products): array
    {
        $primaries = [];

        foreach ($products as $product) {
            if (! is_numeric($product)) {
                throw new Exception(Loc::getMessage('error_incorrect_id'), StatusCodeInterface::STATUS_BAD_REQUEST);
            }

            $primaries[] = [
                'productId' => $product,
                'userId'    => $userId,
            ];
        }

        return $primaries;
    }

    /**
     * @param array $products
     *
     * @return void
     * @throws Exception
     */
    protected function validateProductsOnEmpty(array $products)
    {
        if (empty($products)) {
            throw new Exception(Loc::getMessage('error_empty_products'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }
    }
}
