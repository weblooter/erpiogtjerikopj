<?php

namespace NaturaSiberica\Api\Repositories\User;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\Error\ErrorDTO;
use NaturaSiberica\Api\DTO\User\AddressDTO;
use NaturaSiberica\Api\Entities\UserAddressTable;
use NaturaSiberica\Api\Exceptions\AddressException;
use NaturaSiberica\Api\Exceptions\RepositoryException;
use NaturaSiberica\Api\Factories\AddressDTOFactory;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Repositories\Repository;
use NaturaSiberica\Api\Validators\ResultValidator;
use NaturaSiberica\Api\Validators\User\AddressValidator;

Loc::loadMessages(__FILE__);

class AddressRepository
{
    protected array $select = [
        'id',
        'userId',
        'fiasId',
        'name',
        'region',
        'city',
        'street',
        'houseNumber',
        'flat',
        'floor',
        'entrance',
        'doorPhone',
        'latitude',
        'longitude',
        'default',
        'privateHouse',
    ];
    private Query            $query;
    private AddressValidator $addressValidator;
    private ResultValidator  $resultValidator;
    /**
     * @var AddressDTO[]
     */
    private array $collection = [];

    public function __construct(array $options = [])
    {
        $this->prepareQuery();
        $this->addressValidator = new AddressValidator();
        $this->resultValidator  = new ResultValidator();
    }

    /**
     * @return void
     * @throws ArgumentException
     * @throws SystemException
     */
    private function prepareQuery()
    {
        $this->query = UserAddressTable::query();
        $this->query->setOrder(['id' => 'desc']);
        $this->query->setSelect($this->select);
    }

    /**
     * @param int $id
     *
     * @return bool
     *
     * @throws Exception
     */
    public static function validateId(int $id): bool
    {
        if ((new static)->checkId($id)) {
            return true;
        }

        throw new Exception(
            Loc::getMessage('ERROR_ADDRESS_NOT_FOUND', [
                '#ID#' => $id,
            ]), StatusCodeInterface::STATUS_NOT_FOUND
        );
    }

    /**
     * @param int $id
     *
     * @return bool
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function checkId(int $id): bool
    {
        $check = UserAddressTable::getByPrimary($id, ['select' => ['ID']])->fetch();

        return is_array($check);
    }

    /**
     * @param $addressId
     * @param $userId
     *
     * @return bool
     *
     * @throws Exception
     */
    public static function validateUserAddress($addressId, $userId): bool
    {
        if (! (new static())->checkUserAddress($addressId, $userId)) {
            throw new Exception(Loc::getMessage('ERROR_EDIT_ALIEN_ADDRESS'), StatusCodeInterface::STATUS_FORBIDDEN);
        }

        return true;
    }

    /**
     * @param int $addressId
     * @param int $userId
     *
     * @return bool
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected function checkUserAddress(int $addressId, int $userId): bool
    {
        $check = UserAddressTable::query()->where('id', '=', $addressId)->where('userId', '=', $userId)->setSelect(['id', 'userId'])->fetch();

        return is_array($check);
    }

    /**
     * @param $id
     *
     * @return $this
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function findById($id): AddressRepository
    {
        return $this->findBy('id', $id);
    }

    /**
     * @param string $field
     * @param        $value
     *
     * @return $this
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function findBy(string $field, $value): AddressRepository
    {
        $this->query->addFilter($field, $value);

        return $this->prepareCollection();
    }

    /**
     * @return $this
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    private function prepareCollection(): AddressRepository
    {
        $rows = $this->query->fetchAll();

        if (empty($rows)) {
            return $this;
        }

        foreach ($rows as $row) {
            $this->collection[] = AddressDTOFactory::createFromBitrixFormat($row)->except('userId');
        }

        return $this;
    }

    /**
     * @param int $userId
     *
     * @return $this
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function findByUserId(int $userId): AddressRepository
    {
        return $this->findBy('userId', $userId);
    }

    /**
     * @return array|AddressDTO[]
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function all(): array
    {
        if (empty($this->collection)) {
            $this->prepareCollection();
        }

        return $this->collection;
    }

    /**
     * @return AddressDTO|null
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function get(): ?AddressDTO
    {
        if (empty($this->collection)) {
            $this->prepareCollection();
        }

        return $this->collection[0];
    }

    /**
     * @param AddressDTO|null $object
     *
     * @return AddressDTO|null
     *
     * @throws Exception
     */
    public function create(AddressDTO $object = null): ?AddressDTO
    {
        RepositoryException::assertNotNull('object', $object);

        /**
         * @var AddressDTO $object
         */

        $fields = $object->except('id')->toArray();

        if (empty($fields['name'])) {
            $fields['name'] = UserAddressTable::toString($object->id, $fields);
        }

        $result = UserAddressTable::add($fields);

        $this->resultValidator->validate($result, 'db_error_on_address_create');

        $object->id = $result->getId();
        return $object;
    }

    /**
     * @param DTOInterface $object
     *
     * @return bool
     *
     * @throws Exception
     */
    public function update(DTOInterface $object): bool
    {
        /**
         * @var AddressDTO $object
         */
        $fields = $object->except('id')->toArray();
        $result = UserAddressTable::update($object->id, $fields);

        $this->resultValidator->validate($result, 'db_error_on_address_update');
        return $result->isSuccess();
    }

    /**
     * @param DTOInterface $object
     *
     * @return bool
     *
     * @throws Exception
     */
    public function delete(DTOInterface $object): bool
    {
        /**
         * @var AddressDTO $object
         */

        $result = UserAddressTable::delete($object->id);
        $this->resultValidator->validate($result, 'db_error_on_address_delete');

        return $result->isSuccess();
    }

    protected function prepareUpdateFields(array &$fields): array
    {
        unset($fields['ID']);
        return $fields;
    }
}
