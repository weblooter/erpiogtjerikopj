<?php

namespace Userstory\I18n\Helper;

use Bitrix\Main\Localization\Loc;
use CSite;
use Userstory\I18n\Exception\HelperException;
use Userstory\I18n\Helper\Helper;

/**
 * Class SiteHelper
 * 
 * @package Userstory\I18n\Helper
 */
class SiteHelper extends Helper
{
    /**
     * @param array $filter
     * 
     * @return array
     */
    public function getSites(array $filter = []): array
    {
        $by     = 'def';
        $order  = 'desc';
        $result = [];

        $dbSites = CSite::GetList($by, $order, $filter);
        while ($arSite = $dbSites->Fetch()) {
            $result[$arSite['LID']] = $arSite;
        }

        return $result;
    }

    /**
     * @return array
     * @throws HelperException
     */
    public function getDefaultSiteIfExists(): array
    {
        $items = $this->getSites(['ACTIVE' => 'Y', 'DEFAULT' => 'Y']);

        if (empty($items)) {
            $this->throwException(
                __METHOD__,
                Loc::getMessage(self::MESSAGE_PREFIX . 'ERR_DEFAULT_SITE_NOT_FOUND')
            );
        }

        return array_pop($items);
    }

    /**
     * @return string
     * @throws HelperException
     */
    public function getDefaultSiteIdIfExists(): string
    {
        return $this->getDefaultSiteIfExists()['LID'];
    }

    /**
     * @param string $siteId
     * @param string $siteName
     * 
     * @return string
     * @throws HelperException
     */
    public function copyDefaultSite(string $siteId, string $siteName): string
    {
        $arFields = $defaultSite = $this->getDefaultSiteIfExists();
        
        $arFields['LID']  = $siteId;
        $arFields['NAME'] = $siteName;
        $arFields['DEF']  = 'N';
        $arFields['DIR']  = sprintf('%s/%s/', rtrim($defaultSite['DIR'], '/'), $siteId);

        $obSite = new CSite();
        if (!$newSiteId = $obSite->Add($arFields)) {
            $this->throwException(
                __METHOD__,
                $obSite->LAST_ERROR
            );
        }

        return $newSiteId;
    }
}
