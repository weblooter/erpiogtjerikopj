<?php

namespace NaturaSiberica\Api\Repositories\Structure\Menu;

use Bitrix\Iblock\Model\Section;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\DTO\Structure\Menu\HeaderMenuDTO;

class HeaderMenuRepository
{
    private const IBLOCK_ID = 9;

    /**
     * @var HeaderMenuDTO[]
     */
    private array $collection = [];

    /**
     * @var array|string[]
     */
    private array $select = [
        'ID',
        'NAME',
        'IBLOCK_SECTION_ID',
        'SORT',
        'UF_URL',
        'UF_IS_NEW',
        'UF_BOLD',
        'UF_SHOW_ARROW',
    ];

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    private function getData(): array
    {
        $entity = Section::compileEntityByIblock(self::IBLOCK_ID);
        return $entity::getList(['select' => $this->select, 'filter' => ['ACTIVE' => 'Y']])->fetchAll();
    }

    /**
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ArgumentException
     */
    private function prepareCollection(): void
    {
        $items = $this->getData();

        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            $this->collection[] = HeaderMenuDTO::convertFromBitrixFormat($item);
        }
    }

    /**
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function all(): array
    {
        $this->prepareCollection();
        return $this->collection;
    }
}
