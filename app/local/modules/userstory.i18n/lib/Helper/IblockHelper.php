<?php

namespace Userstory\I18n\Helper;

use Bitrix\Catalog\CatalogIblockTable;
use Userstory\I18n\Helper\Helper;
use Userstory\I18n\Helper\Traits\Iblock\IblockFieldTrait;
use Userstory\I18n\Helper\Traits\Iblock\IblockPropertyTrait;
use Userstory\I18n\Helper\Traits\Iblock\IblockTrait;

/**
 * Class IblockHelper
 * 
 * @package Userstory\I18n\Helper
 */
class IblockHelper extends Helper
{
    use IblockFieldTrait;
    use IblockPropertyTrait;
    use IblockTrait;

    /**
     * @return bool
     */
    protected function isEnabled(): bool
    {
        return $this->checkModules(['iblock', 'catalog']);
    }

    /**
     * Получает список инфоблоков для основной версии сайта
     * 
     * @return array
     */
    public function getIblocksForMainSite(): array
    {
        $siteHelper = new SiteHelper();
        $mainSiteId = $siteHelper->getDefaultSiteIdIfExists();
        $sites      = $siteHelper->getSites();
        
        $filter = [
            'ACTIVE'  => 'Y',
            'SITE_ID' => $mainSiteId
        ];
        if (count($sites) > 1) {
            $filter[] = array_reduce(
                $sites,
                function ($acc, $site) use ($mainSiteId) {
                    if ($site['LID'] !== $mainSiteId) {
                        $acc[] = [
                            '!CODE' => '%_' . strtolower($site['LID'])
                        ];
                    }

                    return $acc;
                },
                ['LOGIC' => 'AND']
            );
        }

        $iblocks = $this->getIblocks($filter);
        return $this->arrangeByKey($iblocks, 'ID');
    }

    /**
     * @param array $filter
     * 
     * @return array
     */
    public function getCatalogIblocks(array $filter = []): array
    {
        $result = [];

        $dbItems = CatalogIblockTable::getList(['filter' => $filter]);
        while ($arItem = $dbItems->fetch()) {
            $result[$arItem['IBLOCK_ID']] = $arItem;
        }

        return $result;
    }

    /**
     * @param int $iblockId
     * @param array $data
     * 
     * @return bool
     */
    public function addCatalogIblock(int $iblockId, array $data): bool
    {
        $data['IBLOCK_ID'] = $iblockId;
        $addResult = CatalogIblockTable::add($data);
        if (!$addResult->isSuccess()) {
            $this->throwException(__METHOD__, implode('; ', $addResult->getErrorMessages()));
        }

        return true;
    }

    /**
     * @param int $iblockId
     * @param array $data
     * 
     * @return bool
     */
    public function updateCatalogIblock(int $iblockId, array $data): bool
    {
        $updateResult = CatalogIblockTable::update($iblockId, $data);
        if (!$updateResult->isSuccess()) {
            $this->throwException(__METHOD__, implode('; ', $updateResult->getErrorMessages()));
        }

        return true;
    }
}
