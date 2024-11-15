<?php

namespace NaturaSiberica\Api\DTO\Sale;

use NaturaSiberica\Api\Traits\NormalizerTrait;
use Spatie\DataTransferObject\DataTransferObject;

class StatusDTO extends DataTransferObject
{
    use NormalizerTrait;

    public string $id;
    public string $name;
    public string $type;
    public int $sort;
    public string $lang;
    public ?string $description = null;

    /**
     * @var int|string|null
     */
    public $xmlId = null;

    public function __construct(array $parameters = [])
    {
        $this->prepareParameters($parameters);
        parent::__construct($parameters);
    }

    protected function prepareParameters(array &$parameters)
    {
        $id = $parameters['ID'];
        $type = $parameters['TYPE'];
        $sort = $parameters['SORT'];

        unset($parameters['ID'], $parameters['SORT'], $parameters['TYPE']);

        $parameters['id'] = $id;
        $parameters['sort'] = $sort;
        $parameters['type'] = $type;

        foreach ($parameters as &$parameter) {
            if (is_numeric($parameter)) {
                $parameter = (int) $parameter;
            }

            if (empty($parameter)) {
                $parameter = null;
            }
        }
    }
}
