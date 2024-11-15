<?php

namespace NaturaSiberica\Api\Repositories\UserField;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\UserFieldTable;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\UserField\EnumFieldDTO;
use NaturaSiberica\Api\DTO\UserField\EnumFieldItemDTO;
use NaturaSiberica\Api\Entities\FieldEnumTable;
use NaturaSiberica\Api\Exceptions\RepositoryException;
use NaturaSiberica\Api\Traits\NormalizerTrait;

class EnumFieldRepository
{
    private Query $query;
    private array $select = [
        'ID',
        'FIELD_NAME' => 'UF.FIELD_NAME',
        'VALUE'
    ];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->prepareQuery();
    }

    /**
     * @return void
     * @throws Exception
     */
    private function prepareQuery()
    {
        $this->query = FieldEnumTable::query();
        $this
            ->query
            ->setSelect($this->select)
            ->registerRuntimeField(
                new Reference('UF', UserFieldTable::class, Join::on('this.USER_FIELD_ID', 'ref.ID'))
            );
            
    }

    private function prepareFieldNameForFilter(string $fieldName): string
    {

        if (stripos($fieldName, 'UF_') !== false) {
            return $fieldName;
        }
        $convertedFieldName = strtoupper(preg_replace('/(?<!^)[A-Z]/', '_$0', $fieldName));
        return sprintf('UF_%s', $convertedFieldName);
    }

    /**
     * @param string $fieldName
     *
     * @return EnumFieldDTO
     * @throws Exception
     */
    public function findByFieldName(string $fieldName): EnumFieldDTO
    {
        $this->query->addFilter('FIELD_NAME', $this->prepareFieldNameForFilter($fieldName));
        
        $rows = $this->query->fetchAll();
        
        if (empty($rows)) {
            throw new Exception(Loc::getMessage('error_no_values'), StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $values = [];
        
        foreach ($rows as $row) {
            $values[] = new EnumFieldItemDTO([
                'id' => (int) $row['ID'],
                'value' => $row['VALUE']
            ]);
        }

        return new EnumFieldDTO([
            'list' => $values
        ]);
    }
    
    
}
