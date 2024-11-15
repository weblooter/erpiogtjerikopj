<?php

namespace NaturaSiberica\Api\V2\Services\User;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Entities\FavouriteTable;
use NaturaSiberica\Api\Helpers\Catalog\ProductsHelper;
use NaturaSiberica\Api\Helpers\CityHelper;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Interfaces\Services\User\FavouritesServiceInterface;
use NaturaSiberica\Api\Repositories\Sale\CartRepository;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Services\Catalog\ProductService;
use NaturaSiberica\Api\Services\User\WishListService;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

Loc::loadMessages(dirname(__DIR__, 3) . '/services/user/wishlistservice.php');

class FavouritesService
{
    use InfoBlockTrait;

    private WishListService $service;
    private int             $iblockId;
    private array           $error = [];

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function __construct()
    {
        $this->service  = new WishListService();
        $this->iblockId = $this->getIblockId(ConstantEntityInterface::IBLOCK_CATALOG);
    }

    /**
     * Получает список товарв по id покупателя
     *
     * @param int $fuserId
     *
     * @return array
     */
    public function getItemsByFuserId(int $fuserId): array
    {
        $productList = $this->getProductList($this->getProductIdsByFuserId($fuserId));
        $data = [
            'fuserId' => $fuserId,
            'count' => count($productList),
            'list' => $productList
        ];

        return $this->prepareFavourites($data);
    }

    /**
     * Получает список товаров по uid
     *
     * @param string $uid
     *
     * @return array
     * @throws Exception
     */
    public function getItemsByUid(string $uid): array
    {
        $repository = new UserRepository();
        $userDTO = $repository->findByUid($uid)->get();

        if ($userDTO === null) {
            throw new Exception(Loc::getMessage('error_uid'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }
        $productList = $this->getProductList($this->getProductIdsByUserId($userDTO->id));
        $data = [
            'fuserId' => $userDTO->fuserId,
            'userId' => $userDTO->id,
            'count' => count($productList),
            'list' => $productList
        ];

        return $this->prepareFavourites($data);
    }

    /**
     * Добавляет товары
     *
     * @param array $userData
     * @param array $body
     *
     * @return array
     * @throws Exception
     */
    public function addItems(array $userData, array $body): array
    {
        if(!$body['items']) {
            throw new Exception('Не переданы товары.', StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }
        $items = array_unique($body['items']);

        if(!$items) {
            throw new Exception('Не переданы товары', StatusCodeInterface::STATUS_BAD_REQUEST);
        }
        $productIds = $this->getProductIdsByFuserId($userData['fuserId']);
        foreach ($items as $item) {
            if(in_array($item, $productIds)) {
                $this->error[] = 'Товар id '.$item.' уже добавлен.';
            } elseif(!CartRepository::checkProduct($item)) {
                $this->error[] = 'Товар id '.$item.' отсутствует в каталоге.';
            } else {
                $fields = ['PRODUCT_ID' => $item, 'FUSER_ID' => $userData['fuserId']];
                if($userData['userId']) {
                    $fields['USER_ID'] = $userData['userId'];
                }
                if(!FavouriteTable::add($fields)->isSuccess()) {
                    $this->error[] = 'Товар id '.$item.' не добавлен в избранное.';
                }
            }
        }

        $productList = $this->getProductList($this->getProductIdsByFuserId($userData['fuserId']));
        $data = [
            'message' => ($this->error ?: 'Товары успешно добавлены в избранное'),
            'fuserId' => $userData['fuserId'],
            'count' => count($productList),
            'list' => $productList
        ];

        return $this->prepareFavourites($data);
    }

    /**
     * @param int   $fuserId
     * @param array $userData
     *
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function transferItems(int $fuserId, array $userData): bool
    {
        $products = [];
        if($fuserId !== $userData['fuserId']) {
            $products = $this->getProductIdsByFUserId($userData['fuserId']);
        }

        $data = FavouriteTable::getList(['filter' => ['FUSER_ID' => $fuserId],'select' => ['ID','PRODUCT_ID']])->fetchCollection();

        if($data && $data->count() > 0) {
            foreach ($data as $item) {
                if(in_array($item->get('PRODUCT_ID'), $products)) {
                    if(!FavouriteTable::delete($item->get('ID'))->isSuccess()) {
                        $this->error[] = 'Товар id '.$item->get('ID').' задублирован в избранном.';
                    }
                } else {
                    if($userData['fuserId'] !== $fuserId) {
                        $item->set('FUSER_ID', $userData['fuserId']);
                    }
                    $item->set('USER_ID', $userData['userId']);
                }
            }
            return $data->save()->isSuccess();
        }

        return false;
    }

    /**
     * Удаляет один товар
     *
     * @param int $fuserId
     * @param int $productId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function deleteItem(int $fuserId, int $productId): array
    {
        $data = FavouriteTable::getList([
            'filter' => ['FUSER_ID' => $fuserId, 'PRODUCT_ID' => $productId],
            'select' => ['ID']
        ])->fetchObject();
        if(!$data) {
            throw new Exception('Товара #'.$productId.' нет.', StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        if(!FavouriteTable::delete($data->getId())->isSuccess()) {
            throw new Exception('Товар #'.$productId.' удалить не удалось.', StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        $productList = $this->getProductList($this->getProductIdsByFuserId($fuserId));
        $data = [
            'fuserId' => $fuserId,
            'count' => count($productList),
            'list' => $productList
        ];

        return $this->prepareFavourites($data);
    }

    /**
     * Удаляет все товары
     *
     * @param int $fuserId
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function deleteAll(int $fuserId): array
    {
        $data = FavouriteTable::getList(['filter' => ['FUSER_ID' => $fuserId],'select' => ['ID']])->fetchCollection();
        if($data && $data->count() > 0) {
            foreach ($data as $item) {
                if(!FavouriteTable::delete($item->getId())->isSuccess()) {
                    $this->error[] = 'Товара #'.$item->getId().' удалить не удалось.';
                }
            }
        }

        return ['message' => ($this->error ?: 'Товары успешно удалены.'),'list' => []];
    }

    /**
     * @param int   $fuserId
     * @param array $select
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getProductIdsByFuserId(int $fuserId, array $select = []): array
    {
        $data = FavouriteTable::getList([
            'filter' => ['FUSER_ID' => $fuserId],
            'select' => ($select ?: ['PRODUCT_ID']),
            'order' => ['DATE_INSERT' => 'desc']
        ])->fetchCollection();
        $result = [];
        if($data && $data->count() > 0) {
            foreach ($data as $item) {
                $result[] = $item->get('PRODUCT_ID');
            }
        }

        return $result;
    }

    /**
     * @param int   $userId
     * @param array $select
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getProductIdsByUserId(int $userId, array $select = []): array
    {
        $data = FavouriteTable::getList([
            'filter' => ['USER_ID' => $userId],
            'select' => ($select ?: ['PRODUCT_ID']),
            'order' => ['DATE_INSERT' => 'desc']
        ])->fetchCollection();
        $result = [];
        if($data && $data->count() > 0) {
            foreach ($data as $item) {
                $result[] = $item->get('PRODUCT_ID');
            }
        }

        return $result;
    }

    /**
     * @param array $ids
     *
     * @return array
     * @throws \NaturaSiberica\Api\Exceptions\RequestBodyException
     */
    public function getProductList(array $ids): array
    {
        $productService = new ProductService();
        $data = $productService->index([
            'filter' => json_encode(['ids' => $ids]),
            'city'   => CityHelper::getCityId(),
        ]);

        return $data['list'];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function prepareFavourites(array &$data): array
    {
        if ($data['list']) {
            ProductsHelper::prepareListImagesForVersion($this->iblockId, $data['list']);
        }

        return $data;
    }
}
