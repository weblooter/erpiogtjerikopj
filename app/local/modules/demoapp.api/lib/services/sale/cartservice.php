<?php

namespace NaturaSiberica\Api\Services\Sale;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ObjectNotFoundException;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Factories\Sale\CartFactory;
use NaturaSiberica\Api\Interfaces\Services\Sale\CartServiceInterface;
use NaturaSiberica\Api\Repositories\Sale\CartRepository;
use NaturaSiberica\Api\Traits\NormalizerTrait;
use ReflectionException;

Loc::loadMessages(__FILE__);

class CartService implements CartServiceInterface
{
    use NormalizerTrait;

    private CartRepository $cartRepository;

    public function __construct()
    {
        $this->cartRepository = new CartRepository();
    }

    /**
     * @param int $fuserId ID покупателя
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws ArgumentTypeException
     * @throws NotImplementedException
     * @throws ServiceException
     * @throws ArgumentNullException
     * @throws LoaderException
     * @throws ObjectNotFoundException
     * @throws ReflectionException
     */
    public function index(int $fuserId): array
    {
        $this->cartRepository->initBasket($fuserId);

        return [
            'cart' => $this->cartRepository->get()->except('fuserId')->toArray()
        ];
    }

    /**
     * @param int   $fuserId ID покупателя
     * @param array $productItems Массив с товарами (передаётся ID товара и его количество)
     *
     * @return array
     * @throws ArgumentException
     * @throws ArgumentTypeException
     * @throws NotImplementedException
     * @throws ServiceException
     * @throws ArgumentNullException
     * @throws ArgumentOutOfRangeException
     * @throws LoaderException
     * @throws NotSupportedException
     * @throws ObjectNotFoundException
     * @throws ReflectionException
     */
    public function update(int $fuserId, array $productItems): array
    {
        $this->cartRepository->initBasket($fuserId);
        $update = $this->cartRepository->update($productItems);

        return [
            'updated' => $update,
            'cart' => $this->cartRepository->get()->except('fuserId')->toArray(),
            'message' => Loc::getMessage('SUCCESSFUL_UPDATED_CART')
        ];
    }

    /**
     * @param int $fuserId ID покупателя
     *
     * @return array
     *
     * @throws ArgumentException
     * @throws ArgumentTypeException
     * @throws LoaderException
     * @throws NotImplementedException
     * @throws ObjectNotFoundException
     * @throws ServiceException
     */
    public function delete(int $fuserId): array
    {
        $delete = $this->cartRepository->initBasket($fuserId)->delete();

        if ($delete) {
            return [
                'deleted' => $delete,
                'message' => Loc::getMessage('SUCCESSFUL_DELETED_CART')
            ];
        }

        throw new ServiceException('Unknown DB error');
    }

    public function exportResult(string $message = null): array
    {
        return [];
    }
}
