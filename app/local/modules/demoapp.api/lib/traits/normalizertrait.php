<?php

namespace NaturaSiberica\Api\Traits;

trait NormalizerTrait
{
    /**
     * Преобразование строки из snake в camel case
     *
     * @param string $str
     *
     * @param bool   $firstToLower Первый символ в нижнем регистре
     * @param bool   $stringToLower Приведение всей строки к нижнему регистру
     *
     * @return string
     */
    protected function convertSnakeToCamel(string $str, bool $firstToLower = true, bool $stringToLower = false): string
    {
        if ($stringToLower) {
            $str = strtolower($str);
        }
        $camelCasedString = preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
            return ('.' === $match[1] ? '_' : '').strtoupper($match[2]);
        }, $str);

        return $firstToLower ? lcfirst($camelCasedString): $camelCasedString;
    }

    /**
     * Преобразование строки из camel в snake case
     * @param string $str
     * @param bool   $uppercase Приведение к верхнему регистру
     *
     * @return string
     */
    protected function convertCamelToSnake(string $str, bool $uppercase = false): string
    {
        $snakeCasedString = strtolower(preg_replace('/[A-Z]/', '_\\0', lcfirst($str)));

        return $uppercase ? strtoupper($snakeCasedString) : $snakeCasedString;
    }

    /**
     * Извлечение названия свойства из названия метода
     *
     * @param string $prefix Префикс метода (get/set/is и т.д.)
     * @param string $method
     * @param bool   $firstToLower
     *
     * @return string
     */
    protected function extractPropertyNameFromMethod(string $prefix, string $method, bool $firstToLower = true): string
    {
        $extracted = str_ireplace($prefix, '', $method);
        return $firstToLower ? lcfirst($extracted): $extracted;
    }

    protected function convertArrayKeysToUpperCase(array &$data)
    {
        foreach ($data as $key => $value) {
            $upper = strtoupper($key);

            if ($upper !== $key) {
                $data[$upper] = $value;
                unset($data[$key]);
            }
        }
    }

    public static function valuesToString(array $data, array $excludeFields = []): string
    {
        foreach (array_keys($data) as $key) {
            if (in_array($key, $excludeFields) || empty($data[$key])) {
                unset($data[$key]);
            }
        }
        return implode(', ', array_values($data));
    }
}
