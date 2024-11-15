<?php

namespace NaturaSiberica\Api\Traits\Entities;

use Bitrix\Main\FileTable;
use NaturaSiberica\Api\Helpers\Http\UrlHelper;

trait FileTrait
{
    /**
     * Получает список относительных путей к изображениям
     *
     * @param array $ids      Список числовых идентификаторов изображений
     * @param bool  $absolute Если true, задаётся абсолютный путь к изображению
     *
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public function getImagePath(array $ids, bool $absolute = true): array
    {
        $result     = [];
        $collection = $this->getRawData([
            'filter' => ['ID' => $ids],
            'select' => ['ID', 'SUBDIR', 'FILE_NAME'],
        ])->fetchCollection();

        foreach ($collection as $item) {
            $result[$item->get('ID')] = $absolute ? UrlHelper::getFileUri($item) : UrlHelper::getFileUrn($item);
        }

        return $result;
    }

    protected function getRawData(array $params)
    {
        return FileTable::getList($params);
    }

    public function getImageData(array $ids): array
    {
        $result     = [];
        $collection = $this->getRawData([
            'filter' => ['ID' => $ids],
            'select' => ['ID', 'SUBDIR', 'FILE_NAME'],
        ])->fetchAll();

        foreach ($collection as &$item) {
            $id                = (int)$item['ID'];
            $item['ID']        = (int)$item['ID'];
            $item['PATH']      = $_SERVER['DOCUMENT_ROOT'] . UrlHelper::getFileUrnFromArray($item);
            $item['IS_EXISTS'] = file_exists($item['PATH']);

            $result[$id] = $item;
        }

        return $result;
    }

    /**
     * @param string|array $fileName название файла
     *
     * @return array
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getIdsByNames($fileName): array
    {
        $files = $this->getRawData([
            'filter' => ['ORIGINAL_NAME' => $fileName],
            'select' => ['ID'],
        ])->fetchCollection();

        return $files->fill('ID');
    }
}
