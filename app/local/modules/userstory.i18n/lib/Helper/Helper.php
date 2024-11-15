<?php

namespace Userstory\I18n\Helper;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use CDBResult;
use ReflectionClass;
use ReflectionException;
use Userstory\I18n\Exception\HelperException;

/**
 * Class Helper
 * 
 * @package Userstory\I18n\Helper
 */
class Helper
{
    public const MESSAGE_PREFIX = 'USERSTORY_I18N__';

    /**
     * Helper constructor
     *
     * @throws HelperException
     */
    public function __construct()
    {
        Loc::loadMessages(__FILE__);

        if (!$this->isEnabled()) {
            $this->throwException(
                __METHOD__,
                Loc::getMessage(
                    self::MESSAGE_PREFIX . 'ERR_HELPER_DISABLED',
                    [
                        '#NAME#' => $this->getHelperName(),
                    ]
                )
            );
        }
    }

    /**
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return true;
    }

    /**
     * @param string $method
     * @param string $msg
     * @param mixed ...$vars
     * 
     * @return void
     * @throws HelperException
     */
    protected function throwException(string $method, string $msg, ...$vars)
    {
        $args    = func_get_args();
        $method  = array_shift($args);
        $message = call_user_func_array('sprintf', $args);

        $message = $this->getMethod($method) . ': ' . strip_tags($message);

        throw new HelperException($message);
    }

    /**
     * @param string $method
     *
     * @throws HelperException
     */
    protected function throwApplicationExceptionIfExists(string $method)
    {
        global $APPLICATION;
        if ($APPLICATION->GetException()) {
            $this->throwException(
                $method,
                $APPLICATION->GetException()->GetString()
            );
        }
    }

    /**
     * @return string
     */
    protected function getHelperName(): string
    {
        try {
            $classInfo = new ReflectionClass($this);
            return $classInfo->getShortName();
        } catch (ReflectionException $e) {
            return 'Helper';
        }
    }

    /**
     * @param array $names
     * 
     * @return bool
     */
    protected function checkModules(array $names = []): bool
    {
        foreach ($names as $name) {
            try {
                if (!Loader::includeModule($name)) {
                    return false;
                }
            } catch (LoaderException $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $method
     * @param array $fields
     * @param array $reqKeys
     * 
     * @throws HelperException
     */
    protected function checkRequiredKeys(string $method, array $fields, array $reqKeys = [])
    {
        foreach ($reqKeys as $name) {
            if (empty($fields[$name])) {
                $this->throwException(
                    $method,
                    Loc::getMessage(
                        self::MESSAGE_PREFIX . 'ERR_EMPTY_REQ_FIELD',
                        [
                            '#NAME#' => $name,
                        ]
                    )
                );
            }
        }
    }

    /**
     * @param CDBResult $dbres
     * @param bool|string $indexKey
     * @param bool|string $valueKey
     *
     * @return array
     */
    protected function fetchAll(CDBResult $dbres, $indexKey = false, $valueKey = false): array
    {
        $res = [];

        while ($item = $dbres->Fetch()) {
            if ($valueKey) {
                $value = $item[$valueKey];
            } else {
                $value = $item;
            }

            if ($indexKey) {
                $indexVal = $item[$indexKey];
                $res[$indexVal] = $value;
            } else {
                $res[] = $value;
            }
        }

        return $res;
    }

    /**
     * @param array $items
     * @param string $key
     * @param mixed $value
     * 
     * @return array
     */
    protected function filterByKey(array $items, string $key, $value): array
    {
        return array_values(
            array_filter(
                $items,
                function ($item) use ($key, $value) {
                    return ($item[$key] == $value);
                }
            )
        );
    }

    /**
     * @param array $data
     * @param string $key
     * 
     * @return array
     */
    protected function arrangeByKey(array $data, string $key): array
    {
        if (!$data || !$key) {
            return $data;
        }

        $result = [];
        foreach ($data as $item) {
            if (!$item[$key]) {
                continue;
            }
            $result[$item[$key]] = $item;
        }

        return $result;
    }

    /**
     * @param string $method
     * 
     * @return string
     */
    private function getMethod(string $method): string
    {
        $path  = explode('\\', $method);
        return array_pop($path);
    }
}
