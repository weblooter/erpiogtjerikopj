<?php

namespace NaturaSiberica\Api\Repositories;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Exception;
use NaturaSiberica\Api\DTO\TokenDTO;
use NaturaSiberica\Api\Entities\TokensTable;
use NaturaSiberica\Api\Exceptions\RepositoryException;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Helpers\UserHelper;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Validators\DTOValidator;
use ReflectionException;

Loc::loadMessages(__FILE__);

class TokenRepository extends Repository implements ModuleInterface
{
    protected array $select = [
        'USER_ID',
        'FUSER_ID',
        'ACCESS_TOKEN',
        'REFRESH_TOKEN',
        'CREATED_AT',
        'UPDATED_AT',
        'ACCESS_TOKEN_EXPIRES_AT',
        'REFRESH_TOKEN_EXPIRES_AT'
    ];

    /**
     * @throws SystemException
     * @throws ArgumentException
     * @throws ReflectionException
     */
    public function __construct(array $options = [])
    {
        $this
            ->addOption($options, self::OPTION_DTO_CLASS, TokenDTO::class)
            ->setQuery(TokensTable::query());

        parent::__construct($options);
    }

    /**
     * @param int $userId
     *
     * @return false|DTOInterface
     *
     * @throws ObjectPropertyException
     * @throws ReflectionException
     * @throws SystemException
     */
    public function findByUserId(int $userId)
    {
        return $this->findBy('USER_ID', $userId);
    }

    /**
     * Поиск токена по ID покупателя
     *
     * @param int $id ID покупателя
     *
     * @return false|DTOInterface
     *
     * @throws ObjectPropertyException
     * @throws ReflectionException
     * @throws SystemException
     */
    public function findById($id)
    {
        return $this->findBy('FUSER_ID', $id);
    }

    /**
     * Поиск токена в БД
     *
     * @param string $token Токен
     * @param string $type Тип токена (access/refresh)
     *
     * @return false|DTOInterface
     *
     * @throws ObjectPropertyException
     * @throws ReflectionException
     * @throws SystemException
     */
    public function findByToken(string $token, string $type)
    {
        $field = sprintf('%s_TOKEN', strtoupper($type));
        return $this->findBy($field, $token);
    }

    /**
     * @param DTOInterface|null $object
     *
     * @return TokenDTO
     *
     * @throws ReflectionException
     * @throws RepositoryException
     * @throws ServiceException
     * @throws ArgumentNullException
     */
    public function create(DTOInterface $object = null): TokenDTO
    {
        RepositoryException::assertNotNull('object', $object);
        /**
         * @var TokenDTO $object
         */
        if ($object->getCreatedAt() === null) {
            $object->setCreatedAt(
                DateTime::createFromTimestamp(time())
            );
        }

        if($object->getUpdatedAt() === null) {
            $object->setUpdatedAt(
                DateTime::createFromTimestamp(time())
            );
        }

        $fields = $object->convertToBitrixFormat();

        $this->prepareFields($fields, ['USER', 'FUSER']);

        DTOValidator::assertRequiredParameters($fields, $object->requiredParameters());

        $add = TokensTable::add($fields);

        if ($add->isSuccess()) {
            return $object;
        }

        throw new RepositoryException(Loc::getMessage('ERROR_ADD_TOKEN_IN_DB'));
    }

    /**
     * @param DTOInterface $object
     *
     * @return bool
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function update(DTOInterface $object): bool
    {
        /**
         * @var TokenDTO $object
         */

        $object->setUpdatedAt(
            DateTime::createFromTimestamp(time())
        );

        $fields = $object->convertToBitrixFormat();

        $this->prepareFields($fields, ['USER', 'FUSER']);

        DTOValidator::assertRequiredParameters($fields, $object->requiredParameters());

        return TokensTable::update($object->getFuserId(), $fields)->isSuccess();
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
         * @var TokenDTO $object
         */

        DTOValidator::assertPropertyNotNull('fuserId', $object);

        UserHelper::clear();

        return TokensTable::delete($object->fuserId)->isSuccess();
    }
}
