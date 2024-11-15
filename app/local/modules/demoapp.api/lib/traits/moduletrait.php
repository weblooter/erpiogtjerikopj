<?php

namespace NaturaSiberica\Api\Traits;

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\IO\InvalidPathException;
use Slim\App;

trait ModuleTrait
{
    /**
     * @param bool $absolute
     *
     * @return array|string|string[]
     */
    public function getModulePath(bool $absolute = true)
    {
        $path = dirname(__DIR__, 2);

        return !$absolute ? str_ireplace($_SERVER['DOCUMENT_ROOT'], '', $path) : $path;
    }

    /**
     * Путь к папке с маршрутами
     *
     * @return string
     */
    protected function getRoutesFolder(): string
    {
        return sprintf('%s/routes', $this->getModulePath());
    }

    /**
     * Подключение файла с маршрутами
     *
     * @param App    $app - экземпляр приложения Slim
     * @param string $file - название файла с маршрутами
     *
     * @return void
     */
    protected function requireRoutesFromFile(App &$app, string $file)
    {
        if(!preg_match('/\.php/', $file, $matches)) {
            $file = sprintf('%s.php', $file);
        }
        $path = sprintf('%s/%s', $this->getRoutesFolder(), $file);

        require_once $path;
    }
}
