<?php

namespace NaturaSiberica\Api\Repositories\Catalog;

use Bitrix\Iblock\PropertyTable;
use NaturaSiberica\Api\Helpers\Http\UrlHelper;
use NaturaSiberica\Api\Repositories\IblockSectionRepository;
use NaturaSiberica\Api\Traits\Entities\FileTrait;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;
use Exception;
use NaturaSiberica\Api\Traits\NormalizerTrait;

class ElementPropertyRepository
{
    use HighloadBlockTrait, InfoBlockTrait, FileTrait, NormalizerTrait;

    protected array $propertyList = [];
    protected int   $iblockId;

    public function __construct(int $iblockId)
    {
        $this->iblockId = $iblockId;
    }

    /**
     * @param array $filter
     * @param array $select
     *
     * @return array
     */
    public function getList(array $filter = [], array $select = []): array
    {
        try {
            return $this->getIblockPropertyList($this->iblockId, $filter, $select);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param $propertyList
     *
     * @return array
     */
    public function getListNameByType($propertyList): array
    {
        $result = [];
        foreach ($propertyList as $item) {
            switch ($item['PROPERTY_TYPE']) {
                case PropertyTable::TYPE_LIST:
                    $result[] = $item['CODE'] . '.ITEM';
                    break;
                case PropertyTable::TYPE_FILE:
                    $result[] = $item['CODE'] . '.FILE';
                    break;
                case PropertyTable::TYPE_ELEMENT:
                    $result[] = $item['CODE'] . '.ELEMENT';
                    break;
                case PropertyTable::TYPE_SECTION:
                    $result[] = $item['CODE'] . '.SECTION';
                    break;
                case PropertyTable::TYPE_STRING:
                case PropertyTable::TYPE_NUMBER:
                    $result[] = $item['CODE'];
                    break;
            }
        }
        return $result;
    }

    /**
     * @param object $data
     *
     * @return string
     */
    public function getListValuePropertyS(object $data): string
    {
        return ($data ? $data->getValue() : '');
    }

    /**
     * @param $data
     *
     * @return int
     */
    public function getListValuePropertyN($data): int
    {
        return ($data ? intval($data->getValue()) : 0);
    }

    /**
     * @param $data
     *
     * @return string
     */
    public function getListValuePropertyF($data): string
    {
        return ($data->getFile() ? UrlHelper::getFileUri($data->getFile()) : '');
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function getListValuePropertyE($data): array
    {
        $element = $data->getElement();
        if ($element) {
            $image = ($element->get('PREVIEW_PICTURE') ? $this->getImagePath([$element->get('PREVIEW_PICTURE')]) : '');
        }
        return [
            'id'          => ($element ? $element->get('ID') : 0),
            'code'        => ($element ? $element->get('CODE') : ''),
            'name'        => ($element ? $element->get('NAME') : ''),
            'image'       => ($image ? $image[$element->get('PREVIEW_PICTURE')] : ''),
            'description' => ($element ? $element->get('PREVIEW_TEXT') : ''),
        ];
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function getListValuePropertyG($data): array
    {
        $section = $data->getSection();

        if ($section) {
            $sectionRepository = new IblockSectionRepository($section->get('IBLOCK_ID'));
            $sectionProperties = $sectionRepository->getIblockSectionPropertyList($sectionRepository->getSectionIblockId(), ['IS_SEARCHABLE' => 'Y']);
            $select            = $sectionRepository->getSectionUserFieldsSelect(true);
            $sectionData       = $sectionRepository->findById($section->get('ID'), $select);
            $image             = ($section->get('DETAIL_PICTURE') ? $this->getImagePath([$section->get('DETAIL_PICTURE')]) : '');
        }

        $result = [
            'id'          => ($section ? $section->get('ID') : 0),
            'code'        => ($section ? $section->get('CODE') : ''),
            'name'        => ($section ? $section->get('NAME') : ''),
            'image'       => ($image ? $image[$section->get('DETAIL_PICTURE')] : ''),
            'description' => ($section ? $section->get('DESCRIPTION') : ''),
        ];

        if ($sectionData) {
            foreach ($sectionData as $key => &$value) {
                $property = $sectionProperties[$key];

                if ($property) {
                    $alias = strtolower(str_ireplace('UF_', '', $key));
                    $alias = $this->convertSnakeToCamel($alias);

                    if ($property['USER_TYPE_ID'] === 'boolean') {
                        $value = (bool)$value;
                    }

                    if (! $result[$key] || ! $result[$alias]) {
                        $result[$alias] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function getListValuePropertyL($data): array
    {
        $item = $data->getItem();
        return [
            'id'    => ($item ? $item->get('ID') : 0),
            'code'  => ($item ? $item->get('XML_ID') : ''),
            'value' => ($item ? $item->get('VALUE') : ''),
        ];
    }

    /**
     * @param object $data
     *
     * @return string
     */
    public function getListValuePropertySHtml(object $data): string
    {
        $text = '';
        if ($data) {
            $item = unserialize($data->getValue());
            $text = preg_replace('/\r\n|\r|\n/u', '', $item['TEXT']);
        }
        return $text;
    }

    /**
     * @param object $data
     * @param array  $referenceList
     *
     * @return array
     */
    public function getListValuePropertySDirectory(object $data, array $referenceList): array
    {
        $result = [];
        if ($data && $referenceList) {
            if ($data->getValue()) {
                foreach ($referenceList[$data->getValue()] as $code => $value) {
                    $result[strtolower($code)] = $value;
                }
            }
        }
        return $result;
    }

    public function getListValuePropertyESku(object $data): int
    {
        return ($data->getElement() ? $data->getElement()->get('ID') : 0);
    }

}
