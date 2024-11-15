<?php

namespace NaturaSiberica\Api\DTO;

use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use NaturaSiberica\Api\Exceptions\ServiceException;
use NaturaSiberica\Api\Interfaces\DTO\DTOInterface;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Interfaces\SerializerInterface;
use NaturaSiberica\Api\Interfaces\Services\TokenServiceInterface;
use NaturaSiberica\Api\Traits\NormalizerTrait;
use NaturaSiberica\Api\Validators\DTOValidator;
use ReflectionClass;
use ReflectionException;
use Spatie\DataTransferObject\DataTransferObject;

/**
 * @deprecated Используйте DataTransferObject
 *
 * @see DataTransferObject
 */
abstract class AbstractDTO implements ModuleInterface, DTOInterface, SerializerInterface
{
    use NormalizerTrait;

    /**
     * @throws ReflectionException
     * @throws ServiceException
     */
    public static function createInstance(array $attributes): DTOInterface
    {
        return new static($attributes);
    }

    /**
     * @param array $attributes Свойства объекта
     *
     * @throws ReflectionException
     * @throws ServiceException
     */
    public function __construct(array $attributes)
    {
        $this->convertArrayKeysToUpperCase($attributes);
        DTOValidator::assertRequiredParameters($attributes, $this->requiredParameters());
        $this->createObject($attributes);
    }

    /**
     * @param string $name Название свойства
     * @param array  $value Значение свойства
     *
     * @return void
     */
    public function setField(string $name, array $value)
    {
        $method = sprintf('set%s', ucfirst($name));

        if (method_exists($this, $method) && property_exists($this, $name)) {
            call_user_func_array([$this, $method], $value);
        }
    }

    /**
     * Создание DTO из массива
     *
     * @param array $attributes Конвертируемый массив
     *
     * @return DTOInterface
     * @throws ReflectionException
     */
    public function createObject(array $attributes): DTOInterface
    {
        foreach ($attributes as $key => &$value) {

            $rc = new ReflectionClass($this);

            $propertyName = $this->convertSnakeToCamel($key, true, true);

            if (property_exists($this, $propertyName)) {

                $property = $rc->getProperty($propertyName);
                $propertyType = $property->getType()->getName();

                if(in_array($propertyType, $this->notObjectTypes())) {

                    if ($propertyType === 'bool' && $value === null) {
                        $value = false;
                    } elseif ($value instanceof Date || $value instanceof DateTime) {
                        $format = $value instanceof DateTime ? TokenServiceInterface::DEFAULT_DATETIME_FORMAT : TokenServiceInterface::DEFAULT_DATE_FORMAT;
                        $value = $value->format($format);
                    }

                    $value = [$value];
                } else {
                    $propRc = new ReflectionClass($propertyType);
                    $propInstance = $value !== null ? $propRc->newInstance($value) : $value;

                    $value = [$propInstance];
                }

            }

            if (!is_array($value)) {
                $value = [$value];
            }

            $this->setField($propertyName, $value);

        }
        return $this;
    }

    /**
     * Преобразование DTO в массив
     *
     * @param bool              $keyToUpper Приведение ключей массива к верхнему регистру
     * @param DTOInterface|null $object
     * @param bool              $returnDateTimeObject Если true, возвращается объект DateTime
     *
     * @return array
     *
     * @throws ReflectionException
     */
    public function toArray(bool $keyToUpper = false, DTOInterface $object = null, bool $returnDateTimeObject = false): array
    {
        $result = [];
        if($object === null) {
            $object = $this;
        }
        $rc = new ReflectionClass($object);

        foreach ($rc->getProperties() as $property) {
            if($property->getType()->getName() === 'bool' && method_exists($rc->getName(), sprintf('is%s', ucfirst($property->getName())))) {
                $method = sprintf('is%s', ucfirst($property->getName()));
            } elseif (method_exists($rc->getName(), sprintf('get%s', ucfirst($property->getName())))) {
                $method = sprintf('get%s', ucfirst($property->getName()));
            }

            $field = $this->convertCamelToSnake($property->getName(), $keyToUpper);
            $value = call_user_func([$object, $method]);

            if (get_parent_class($value) === __CLASS__) {
                $result[$field] = $this->toArray($keyToUpper, $value, $returnDateTimeObject);
            } elseif ($value instanceof DateTime || $value instanceof Date) {
                $result[$field] = $returnDateTimeObject ? $value : $value->format(TokenServiceInterface::DEFAULT_DATETIME_FORMAT);
            } elseif (is_array($value)) {
                /**
                 * @var DTOInterface $item
                 */
                foreach ($value as $key => &$item) {
                    if (get_parent_class($item) === __CLASS__) {
                        $array = $this->toArray($keyToUpper, $item, $returnDateTimeObject);
                        $value[$key] = $array;
                    }
                }
                $result[$field] = $value;
            } else {
                $result[$field] = $value;
            }
        }

        return array_merge_recursive($result);
    }

    /**
     * @param array $fields
     * @param bool  $keyToUpper
     *
     * @return DTOInterface
     *
     * @throws ReflectionException
     */
    public function modify(array $fields, bool $keyToUpper = false): DTOInterface
    {
        $converted = $this->toArray($keyToUpper);

        foreach ($fields as $key => &$value) {
            $converted[$key] = $value;
        }

        return $this->createObject($converted);
    }

    protected function notObjectTypes(): array
    {
        return ['int', 'float', 'bool', 'string', 'array'];
    }

    /**
     * Массив с обязательными параметрами для валидации данных
     * @return array
     */
    abstract protected function requiredParameters(): array;
}
