<?php

namespace NaturaSiberica\Api\Services\User;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Interfaces\Services\User\FavouritesServiceInterface;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Repositories\User\WishListRepository;

Loc::loadMessages(__FILE__);

class WishListService implements FavouritesServiceInterface
{
    private WishListRepository $wishListRepository;
    private UserRepository     $userRepository;

    public function __construct()
    {
        $this->wishListRepository = new WishListRepository();
        $this->userRepository     = new UserRepository();
    }

    /**
     * @return UserRepository
     */
    public function getUserRepository(): UserRepository
    {
        return $this->userRepository;
    }

    /**
     * @param int $userId
     *
     * @return array
     */
    public function index(int $userId): array
    {
        return $this->wishListRepository->findByUser($userId)->except('userId')->toArray();
    }

    /**
     * @param int   $userId
     * @param array $products
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws SystemException
     * @throws ObjectPropertyException
     * @throws RequestBodyException
     */
    public function addProduct(int $userId, array $products): array
    {
        return array_merge(
            ['message' => Loc::getMessage('successful_add_product_to_wishlist')],
            $this->wishListRepository->add($userId, array_unique($products))->except('userId')->toArray()
        );
    }

    /**
     * @param int $userId
     * @param int $productId
     *
     * @return array|null[]|string[]
     * @throws Exception
     */
    public function deleteProduct(int $userId, int $productId): array
    {
        return array_merge(
            [
                'message' => Loc::getMessage('successful_delete_product_from_wishlist', [
                    '#product_id#' => sprintf('#%s', $productId),
                ]),
            ],
            $this->wishListRepository->delete($userId, $productId)->except('userId')->toArray()
        );
    }

    /**
     * @param int $userId
     *
     * @return array|null[]|string[]
     *
     * @throws Exception
     */
    public function clear(int $userId): array
    {
        $dto = $this->wishListRepository->findByUser($userId);

        if (empty($dto->list)) {
            throw new Exception(Loc::getMessage('error_nothing_delete_from_wishlist'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        return array_merge(
            ['message' => Loc::getMessage('successful_favourites_clear')],
            $this->wishListRepository->clear($userId)->except('userId')->toArray()
        );
    }

    /**
     * @param string $uid
     *
     * @return array
     * @throws Exception
     */
    public function getByUid(string $uid): array
    {
        $userDTO = $this->userRepository->findByUid($uid)->get();

        if ($userDTO === null) {
            throw new Exception(Loc::getMessage('error_uid'), StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        return $this->wishListRepository->findByUser($userDTO->id)->except('userId')->toArray();
    }
}
