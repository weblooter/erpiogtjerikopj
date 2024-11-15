<?php

namespace NaturaSiberica\Api\Helpers\Http;

use Bitrix\Main\Config\Option;

class UrlHelper
{

    /**
     * @return string
     */
    public static function getServerName(): string
    {
        return Option::get('main', 'server_name');
    }

    /**
     * @return string
     */
    public static function getUploadDir(): string
    {
        return Option::get('main', 'upload_dir');
    }

    /**
     * @return string
     */
    public static function getUrl(): string
    {
        return RequestHelper::getHttpScheme().self::getServerName();
    }

    /**
     * @param object $file
     *
     * @return string
     */
    public static function getFileUrn(object $file): string
    {
        return '/'.self::getUploadDir().'/'.$file->get('SUBDIR').'/'.$file->get('FILE_NAME');
    }

    public static function getFileUrnFromArray(array $row, string $subDirField = 'SUBDIR', string $fileNameField = 'FILE_NAME'): string
    {
        return '/' . self::getUploadDir() . '/' . $row[$subDirField] . '/' . $row[$fileNameField];
    }

    public static function getFileUrlFromArray(array $row, string $subDirField = 'SUBDIR', string $fileNameField = 'FILE_NAME'): string
    {
        return self::getUrl() . '/' . self::getUploadDir() . '/' . $row[$subDirField] . '/' . $row[$fileNameField];
    }

    /**
     * @param object $file
     *
     * @return string
     */
    public static function getFileUri(object $file): string
    {
        return self::getUrl().self::getFileUrn($file);
    }

    public static function getImageUrl(string $imagePath): string
    {
        return self::getUrl() . $imagePath;
    }
}
