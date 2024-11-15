<?php

namespace NaturaSiberica\Api\Factories\Content\Pages;

use Bitrix\Iblock\InheritedProperty\ElementValues;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\Content\Pages\PageDTO;
use NaturaSiberica\Api\DTO\Content\Pages\PageItemDTO;
use NaturaSiberica\Api\Helpers\ContentHelper;
use NaturaSiberica\Api\Helpers\Http\UrlHelper;
use NaturaSiberica\Api\Interfaces\Repositories\Content\Pages\PageRepositoryInterface;

Loc::loadMessages(dirname(__DIR__, 3) . '/repositories/content/pages/pagerepository.php');

class PageDTOFactory
{
    /**
     * @param PageRepositoryInterface $repository
     *
     * @return PageDTO|PageItemDTO
     *
     * @throws Exception
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function create(PageRepositoryInterface $repository)
    {
        $attrs      = $repository->getQuery()->fetchAll();
        $one        = 1;

        if (empty($attrs)) {
            throw new Exception(Loc::getMessage('error_page_not_found'), StatusCodeInterface::STATUS_NOT_FOUND);
        }

        if (count($attrs) === $one && empty($repository->getIblockSection())) {
            $attrs        = $attrs[0];
            $attrs['SEO'] = $repository->prepareSeo(ElementValues::class, $attrs['ID']);
            unset($attrs['ID']);

            if (! empty($attrs['DETAIL_PICTURE'])) {
                $attrs['PICTURE'] = UrlHelper::getFileUrlFromArray($attrs);
            }

            return PageItemDTO::convertFromBitrixFormat($attrs);
        }

        $section = $repository->getIblockSection();

        $items = [];

        foreach ($attrs as $attr) {
            $item = [
                'name'    => $attr['NAME'],
                'code'    => $attr['CODE'],
                'content' => ContentHelper::replaceImageUrl($attr['CONTENT']),
                'seo'     => $repository->prepareSeo(ElementValues::class, $attr['ID']),
            ];

            if (! empty($attr['DETAIL_PICTURE'])) {
                $item['image'] = UrlHelper::getFileUrlFromArray($attr);
            }

            $seo = $repository->prepareSeo(ElementValues::class, $attr['ID']);

            if (! empty($seo)) {
                $item['seo'] = $seo;
            }

            $items[] = new PageItemDTO($item);
        }

        $pageAttrs = [
            'name'  => $section['NAME'],
            'code'  => $section['CODE'],
            'description'  => $section['DESCRIPTION'],
            'isWhiteHeaderInMP' => (bool) $section['UF_MP_WHITE_HEADER'],
            'items' => $items,
        ];

        if (! empty($section['PICTURE'])) {
            $pageAttrs['image'] = UrlHelper::getFileUrlFromArray($section);
        }

        return new PageDTO($pageAttrs);
    }
}
