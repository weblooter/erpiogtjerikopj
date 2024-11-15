<?php

namespace NaturaSiberica\Api\Repositories\Catalog;

use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\Type\DateTime;

class ElementFieldRepository
{
    protected array $imageList = [];

    public function setImageList(array $data)
    {
        $this->imageList = $data;
    }

    /**
     * @param int $value
     *
     * @return array
     */
    public function getId(int $value): array
    {
        return ['id' => intval($value)];
    }

    /**
     * @param string $value
     *
     * @return bool[]
     */
    public function getActive(string $value): array
    {
        return ['active' => (bool)$value];
    }

    /**
     * @param string $value
     *
     * @return string[]
     */
    public function getXmlId(string $value): array
    {
        return ['xml_id' => $value];
    }

    /**
     * @param string $value
     *
     * @return string[]
     */
    public function getCode(string $value): array
    {
        return ['code' => $value];
    }

    /**
     * @param string $value
     *
     * @return string[]
     */
    public function getName(string $value): array
    {
        return ['name' => $value];
    }

    /**
     * @param int $value
     *
     * @return int[]
     */
    public function getSort(int $value): array
    {
        return ['sort' => $value];
    }

    /**
     * @param int|null $value
     *
     * @return array|string[]
     */
    public function getPreviewPicture(?int $value): array
    {
        return ['thumbnail' => ($value ? $this->imageList[$value] : '')];
    }

    /**
     * @param int|null $value
     *
     * @return array|string[]
     */
    public function getDetailPicture(?int $value): array
    {
        return ['image' => ($value ? $this->imageList[$value] : '')];
    }

    /**
     * @param string $value
     *
     * @return array
     */
    public function getPreviewText(string $value): array
    {
        return ['excerpt' => htmlspecialchars(preg_replace('/\r\n|\r|\n/u', '', $value))];
    }

    /**
     * @param string $value
     *
     * @return array
     */
    public function getDetailText(string $value): array
    {
        return ['description' => htmlspecialchars(preg_replace('/\r\n|\r|\n/u', '', $value))];
    }

    /**
     * @param DateTime $value
     *
     * @return array
     */
    public function getDateCreate(DateTime $value): array
    {
        return ['create_date' => $value->getTimestamp()];
    }

    /**
     * @param Collection $sections
     *
     * @return array[]
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public function getSections(Collection $sections): array
    {
        $result = [];
        foreach ($sections->getAll() as $section) {
            $result['category'][] = [
                'id'          => $section->get('ID'),
                'name'        => $section->get('NAME'),
                'code'        => $section->get('CODE'),
                'sort' => $section->get('LEFT_MARGIN'),
                'description' => $section->get('DESCRIPTION')
            ];
            
            if($section->get('IBLOCK_SECTION_ID') > 0) {
                $result['product_type_id'][] = $section->get('ID');
            }

        }
        return $result;
    }

    /**
     * @param $value
     *
     * @return array
     */
    public function getShowCounter($value): array
    {
        return ['popular' => $value];
    }
}
