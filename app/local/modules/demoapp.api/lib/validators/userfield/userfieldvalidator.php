<?php

namespace NaturaSiberica\Api\Validators\UserField;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\Entities\FieldEnumTable;
use NaturaSiberica\Api\Exceptions\RepositoryException;

Loc::loadMessages(__FILE__);

class UserFieldValidator
{
    /**
     * @param Query  $query
     * @param string $field
     * @param        $value
     *
     * @return bool
     * @throws Exception
     */
    public static function checkValue(Query $query, string $field, $value): bool
    {
        $data = $query->addFilter($field, $value)->fetch();

        return is_array($data);
    }
}
