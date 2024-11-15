<?php

namespace NaturaSiberica\Api\Repositories;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\DTO\AbstractDTO;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Interfaces\Repositories\RepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionException;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * @deprecated Данный класс нуждается в рефакторинге, поэтому пока использовать его не рекомендуется
 */
abstract class Repository implements RepositoryInterface
{
    const OPTION_DTO_CLASS = 'dto_class';

    protected Query            $query;
    protected ?DTOInterface    $object          = null;
    protected ?ReflectionClass $reflectionClass = null;

    protected ServerRequestInterface $request;

    protected ?string $dtoClass = null;

    /**
     * @var DTOInterface[]
     */
    protected array $collection = [];

    /**
     * Список полей для выборки
     * @var array
     */
    protected array $select;

    /**
     * @var Field[]
     */
    protected array $runtime = [];

    protected array $options = [];

    /**
     * @param array $options
     *
     * @throws ArgumentException
     * @throws ReflectionException
     * @throws SystemException
     */
    public function __construct(array $options = [])
    {
        $this->request = ServerRequestFactory::createFromGlobals();

        if (array_key_exists(self::OPTION_DTO_CLASS, $options)) {
            $this->dtoClass = $options[self::OPTION_DTO_CLASS];
            $this->reflectionClass = new ReflectionClass($this->dtoClass);
        }

        if (!empty($this->select)) {
            $this->query->setSelect($this->select);
        }

        if (!empty($this->runtime)) {
            $this->registerRuntimeFields();
        }
    }

    /**
     * @return array
     */
    public function getSelect(): array
    {
        return $this->select;
    }

    /**
     * @return Field[]
     */
    public function getRuntime(): array
    {
        return $this->runtime;
    }

    /**
     * @param Query $query
     *
     * @return Repository
     */
    public function setQuery(Query $query): Repository
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    public function addFilter(...$filter): Repository
    {
        $this->query->where(...$filter);
        return $this;
    }

    /**
     * @throws SystemException
     * @throws ArgumentException
     */
    protected function registerRuntimeFields(): Repository
    {
        /**
         * @var Field $field
         */
        foreach ($this->runtime as $field) {
            $this->query->registerRuntimeField($field);
        }

        return $this;
    }

    /**
     * @return AbstractDTO|null
     */
    public function getObject(): ?DTOInterface
    {
        return $this->object;
    }

    /**
     * @param AbstractDTO $object
     *
     * @return Repository
     */
    public function setObject(DTOInterface $object): Repository
    {
        $this->object = $object;
        return $this;
    }

    protected function prepareFields(array &$fields, array $excluded)
    {
        foreach ($excluded as $item) {
            unset($fields[$item]);
        }
    }

    /**
     * @return DTOInterface[]
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ReflectionException
     */
    public function all(bool $toArray = false): array
    {
        foreach ($this->query->fetchAll() as $item) {
            /**
             * @var DTOInterface $instance
             */
            $instance = $this->reflectionClass->newInstance($item);
            $this->collection[] = $toArray ? $instance->toArray() : $instance;
        }

        return $this->collection;
    }

    /**
     * @param bool $toArray
     *
     * @return DTOInterface|array
     *
     * @throws ObjectPropertyException
     * @throws ReflectionException
     * @throws SystemException
     */
    public function one(bool $toArray = false)
    {
        $row = $this->query->fetch();
        /**
         * @var DTOInterface
         */
        $instance =  $this->reflectionClass->newInstance($row);
        return $toArray ? $row : $instance;
    }

    /**
     * @param string $field
     * @param        $value
     *
     * @return false|DTOInterface
     *
     * @throws ObjectPropertyException
     * @throws ReflectionException
     * @throws SystemException
     */
    public function findBy(string $field, $value)
    {
        $this->query->whereLike($field, $value);

        $attrs = $this->query->fetch();

        if ($attrs) {
            /**
             * @var DTOInterface $instance
             */
            $instance = $this->reflectionClass->newInstance($attrs);
            return $instance;
        }

        return false;
    }

    /**
     * @param int|string $id
     *
     * @return false|DTOInterface
     * @throws ObjectPropertyException
     * @throws ReflectionException
     * @throws SystemException
     */
    public function findById($id)
    {
        return $this->findBy('ID', $id);
    }

    protected function addOption(array &$options, string $optionName, $value, bool $override = true): Repository
    {
        if (!isset($options[$optionName]) || $override) {
            $options[$optionName] = $value;
        }

        return $this;
    }

    public function create()
    {
    }

    public function update(DTOInterface $object): bool
    {
        return true;
    }

    public function delete(DTOInterface $object): bool
    {
        return true;
    }

    protected function addErrorLog(array $errorMessages, string $errorTypeItemId)
    {
        \CEventLog::Log(
            'ERROR',
            'API_ERROR',
            ModuleInterface::MODULE_ID,
            $errorTypeItemId,
            json_encode($errorMessages, JSON_UNESCAPED_UNICODE)
        );
    }
}
