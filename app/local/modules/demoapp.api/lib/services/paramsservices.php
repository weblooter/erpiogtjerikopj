<?php

namespace NaturaSiberica\Api\Services;

use Bitrix\Main\Loader;
use CSearchLanguage;
use NaturaSiberica\Api\Exceptions\RequestBodyException;

Loader::includeModule('search');

class ParamsServices
{

    public function prepareRequiredParams(array $params, array $required): void
    {
        if (!empty(array_diff($required, array_keys($params)))) {
            throw new RequestBodyException(
                sprintf('Required parameters [%s] are missing', implode(', ',array_diff($required, array_keys($params))))
            );
        }
    }

    public function prepareIntParam(string $param, string $value, ?int $min = null, ?int $max = null): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false) {
            throw new RequestBodyException('Parameter [' . $param . '] must be integer');
        }

        if (!is_null($min) && ($value < $min)) {
            throw new RequestBodyException(sprintf('Parameter [%s] must be integer greater or equal %s', $param, $min));
        }

        if (!is_null($max) && ($value > $max)) {
            throw new RequestBodyException(sprintf('Parameter [%s] must be integer less or equal %s', $param, $max));
        }

        return $value;
    }

    public function prepareStringParams(string $param, string $value, int $length): string
    {
        if (mb_strlen($value) < $length) {
            throw new RequestBodyException('Length of parameter ['.$param.'] must be longer than ' . $length . ' symbols');
        }
        return trim($value);
    }

    public function prepareListParams(string $param, string $value): array
    {
        if(gettype($value) !== 'string') {
            throw new RequestBodyException('Parameter ['.$param.'] must be json string.');
        }
        $list = json_decode($value, true);
        return array_filter($list, function($item){
            if (is_numeric($item)) {
                return (int)$item;
            } else {
                return $item;
            }
//            if(filter_var($num, FILTER_VALIDATE_INT) === false || !is_int($num)) {
//                throw new RequestBodyException('List item [' . $num . '] in the parameter must be a integer number');
//            }
//            return intval($num);
        });
    }

    public function prepareKeyboard(string $query): string
    {
        $arLang = CSearchLanguage::GuessLanguage($query);

        if (is_array($arLang) && ($arLang['from'] !== $arLang['to'])) {
            $query = CSearchLanguage::ConvertKeyboardLayout($query, $arLang['from'], $arLang['to']);
        }
        return $query;
    }
}
