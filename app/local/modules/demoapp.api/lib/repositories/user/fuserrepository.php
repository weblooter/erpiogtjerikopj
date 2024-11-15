<?php

namespace NaturaSiberica\Api\Repositories\User;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Internals\FuserTable;
use Exception;
use NaturaSiberica\Api\DTO\User\FuserDTO;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Helpers\UserHelper;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Repositories\Repository;
use NaturaSiberica\Api\Validators\DTOValidator;
use ReflectionException;

Loader::includeModule('sale');

class FuserRepository extends Repository
{
    protected array $select = [
        'ID',
        'USER_ID'
    ];

    /**
     * @throws ReflectionException
     * @throws SystemException
     * @throws ArgumentException
     */
    public function __construct(array $options = [])
    {
        $this
            ->addOption($options, self::OPTION_DTO_CLASS, FuserDTO::class)
            ->setQuery(FuserTable::query());

        parent::__construct($options);
    }

    /**
     * @return FuserDTO
     *
     * @throws ArgumentException
     * @throws ReflectionException
     * @throws ServiceException
     * @throws SystemException
     */
    public function create(): ?FuserDTO
    {
        UserHelper::clear();

        $id = Fuser::getId();
        $this->object = new FuserDTO([
            'ID' => $id,
            'USER_ID' => Fuser::getUserIdById($id)
        ]);

        return $this->object;
    }

    /**
     * @return int
     *
     * @throws ArgumentException
     * @throws ReflectionException
     * @throws ServiceException
     * @throws SystemException.
     */
    public function getId(): int
    {
        return $this->create()->getId();
    }

    /**
     * @throws ServiceException
     * @throws Exception
     */
    public function update(DTOInterface $object): bool
    {
        /**
         * @var FuserDTO $object
         */
        DTOValidator::assertNotEmpty('USER_ID', $object->toArray(true));

        $updFields = [
            'USER_ID' => $object->getUserId()
        ];

        return FuserTable::update($object->getId(), $updFields)->isSuccess();
    }

    /**
     * @param DTOInterface $object
     *
     * @return bool
     *
     * @throws ServiceException
     * @throws Exception
     */
    public function delete(DTOInterface $object): bool
    {
        /**
         * @var FuserDTO $object
         */
        DTOValidator::assertIdNotNull($object);

        return FuserTable::delete($object->getId())->isSuccess();
    }
}
