<?php

namespace NaturaSiberica\Api\Helpers\Thumbnailer;

use NaturaSiberica\Api\Tools\Settings\Options;

class ImageResizeHelper
{
    /**
     * Получение нарезанных изображений из сервиса
     *
     * @param string $imageName
     * @param string $fieldName
     * @param string $place
     * @param int    $iblockId
     *
     * @return array|string
     */
    public static function getResizedImages(string $imageName, string $fieldName, string $place, int $iblockId)
    {
        $images  = [];
        $configs = Options::getThumbnailerImagesSizesConfigs();
        $key     = sprintf('%s_%s', $fieldName, $place);

        if (array_key_exists($key, $configs) && $configs[$key]['iblock_id'] === $iblockId) {
            $sizes = $configs[$key]['sizes'];

            foreach ($sizes as $level => $size) {
                $code = sprintf('w%s', $size);

                $resizedImagePath = self::getPublicImagePath($imageName, $code);

                if (gettype($level) === 'integer' && count($sizes) === 1) {
                    return $resizedImagePath;
                }

                $images[$level] = $resizedImagePath;
            }
        }

        return $images;
    }

    /**
     * Получение пути к нарезанному изображению
     *
     * @param string $image
     * @param string $code
     * @param bool   $absolute
     *
     * @return string
     */
    public static function getPublicImagePath(string $image, string $code, bool $absolute = true): string
    {
        $imageBaseName = pathinfo($image, PATHINFO_BASENAME);
        $imageFileName = pathinfo($image, PATHINFO_FILENAME);
        $extension     = Options::getThumbnailerImagesExtension();
        $subDir        = self::getPublicSubDirName($imageBaseName, $code);
        $path          = sprintf('/images/%s/%s.%s', $subDir, $imageFileName, $extension);

        return $absolute ? Options::getThumbnailerServiceUrl() . $path : $path;
    }

    /**
     * Возвращает путь к поддиректории, где хранятся файлы или куда их сохранять.
     *
     * @param string      $imageName   Имя изображения или файла без расширения.
     * @param string|null $codeDirName Код группы соответствующий имени директории.
     *
     * @return string
     */
    public static function getPublicSubDirName(string $imageName, string $codeDirName = null): string
    {
        $fileName            = pathinfo($imageName, PATHINFO_FILENAME);
        $md5FileName         = md5($fileName);
        $firstPartSubDirName = substr($md5FileName, 0, 2);
        $secondPartSubDrName = substr($md5FileName, 2, 3);

        $pathToSubDirName = $firstPartSubDirName . '/' . $secondPartSubDrName;

        return (null === $codeDirName) ? $pathToSubDirName : $codeDirName . '/' . $pathToSubDirName;
    }
}
