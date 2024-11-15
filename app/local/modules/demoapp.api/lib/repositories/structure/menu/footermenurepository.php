<?php

namespace NaturaSiberica\Api\Repositories\Structure\Menu;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\DTO\Structure\Menu\FooterMenuCollection;
use NaturaSiberica\Api\DTO\Structure\Menu\FooterMenuDTO;
use NaturaSiberica\Api\Exceptions\Http\HttpUnprocessableEntityException;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Tools\Settings\Settings;

Loader::includeModule('highloadblock');
Loc::loadMessages(__FILE__);

class FooterMenuRepository implements ModuleInterface
{
    private Query $query;
    private array $select = [
        'url' => 'UF_URL',
        'text' => 'UF_URL_TEXT',
        'sort' => 'UF_SORT'
    ];

    public function __construct()
    {
        $this->setQuery();
    }

    private function setQuery()
    {
        $this->query = $this->getEntity()->getDataClass()::query();
        $this->query->setSelect($this->select)->setOrder(['UF_SORT' => 'ASC']);

        return $this;
    }

    protected function getHlEntityId(): int
    {
        return (int) Option::get(self::MODULE_ID, 'footer_menu_hl_entity');
    }

    public function getCollection()
    {
        $settings = new Settings();
        $items = $settings->getMenuItemsFromModule();
        $pages = $this->query->fetchAll();

        if (!empty($pages)) {
            foreach ($pages as &$page) {
                $page['sort'] = (int) $page['sort'];
                $items['pages'][] = new FooterMenuDTO($page);
            }
        }

        return new FooterMenuCollection($items);
    }

    /**
     * @return \Bitrix\Highloadblock\Entity\Base|\Bitrix\Main\Entity\Base
     *
     * @throws HttpUnprocessableEntityException
     * @throws SystemException
     */
    public function getEntity()
    {
        $id = $this->getHlEntityId();
        $this->validateHlEntity($id);

        return HighloadBlockTable::compileEntity($id);
    }

    /**
     * @param int $id
     *
     * @return bool
     *
     * @throws HttpUnprocessableEntityException
     */
    protected function validateHlEntity(int $id)
    {
        if ($id > 0) {
            return true;
        }

        throw new HttpUnprocessableEntityException(Loc::getMessage('ERROR_HL_ENTITY_NOT_DEFINED'));
    }
}
