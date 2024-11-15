<?php

namespace NaturaSiberica\Api\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileNotFoundException;
use Exception;
use NaturaSiberica\Api\Interfaces\ModuleInterface;

class ModuleHelper implements ModuleInterface
{
    /**
     * @param string $moduleId
     * @param bool   $absolute
     *
     * @return string
     *
     * @throws Exception
     */
    public static function getModulePath(string $moduleId = self::MODULE_ID, bool $absolute = true): string
    {
        $folders = ['bitrix', 'local'];
        $root = Application::getDocumentRoot();

        foreach ($folders as $folder) {
            $path = sprintf(
                '%s/%s/modules/%s',
                $root,
                $folder,
                $moduleId
            );

            if (Directory::isDirectoryExists($path)) {
                return !$absolute ? str_ireplace($root, '', $path) : $path;
            }
        }

        throw new Exception('Not exists');
    }

    public static function getModuleFile(string $path)
    {
        $file = sprintf('%s/%s', self::getModulePath(), $path);

        if (!File::isFileExists($file)) {
            throw new FileNotFoundException($file);
        }

        return $file;
    }
}
