<?php

namespace NaturaSiberica\Api\V2\Services\Marketing;

use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Type\DateTime;
use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Marketing\PromoBannerRepository;
use NaturaSiberica\Api\Services\ParamsServices;
use NaturaSiberica\Api\Traits\Entities\FileTrait;

class PromoBannerService
{
    use FileTrait;

    protected ?PromoBannerRepository $repository = null;
    protected array $params = [];
    protected bool $isBrand = false;
    protected bool $isSection = false;
    protected int $parentId = 0;


    public function index(array $params = []): array
    {
        if(is_null($this->repository)) {
            $this->init($params);
        }

        $collection = $this->repository
            ->setSelect(['SECTION_DISPLAY.SECTION', 'BRAND_DISPLAY.SECTION'], true)
            ->getCollection($this->prepareFilter());

        $result = ['top' => null, 'bottom' => null];
        if($collection && $collection->count() > 0) {

            if($this->isSection) {
                $result = $this->getListForSection($collection);
            } elseif($this->isBrand) {
                $result = $this->getListForBrand($collection);
            } else {
                $result = $this->getList($collection);
            }

        }

        return $result;
    }

    protected function getList($collection): array
    {
        $result = [];
        foreach ($collection as $item) {
            $element  = $this->toArray($item);
            if($element['position'] === 'top') {
                if(!$element['brand'] && !$element['section']) {
                    $result['top'] = $element;
                }
            } elseif($element['position'] === 'bottom') {
                if(!$element['brand'] && !$element['section']) {
                    $result['bottom'] = $element;
                }
            }
        }

        return $result;
    }

    protected function getListForBrand($collection): array
    {
        $data = [];
        foreach ($collection as $item) {
            $element  = $this->toArray($item);
            if($element['position'] === 'top') {
                if($element['brand']) {
                    $data['top']['brand'] = $element;
                } else {
                    $data['top']['common'] = $element;
                }
            } elseif($element['position'] === 'bottom') {
                if($element['brand']) {
                    $data['bottom']['brand'] = $element;
                } else {
                    $data['bottom']['common'] = $element;
                }
            }
        }
        $result['top'] = ($data['top']['brand'] ?: $data['top']['common']);
        $result['bottom'] = ($data['bottom']['brand'] ?: $data['bottom']['common']);

        return $result;
    }

    protected function getListForSection($collection): array
    {
        $data = [];
        foreach ($collection as $item) {
            $element  = $this->toArray($item);
            if($element['position'] === 'top') {
                if($element['section'] && $this->parentId === $element['section']) {
                    $data['top']['parent'] = $element;
                } elseif($element['section']) {
                    $data['top']['section'] = $element;
                } else {
                    $data['top']['common'] = $element;
                }
            } elseif($element['position'] === 'bottom') {
                if($element['section'] && $this->parentId === $element['section']) {
                    $data['bottom']['parent'] = $element;
                } elseif($element['section']) {
                    $data['bottom']['section'] = $element;
                } else {
                    $data['bottom']['common'] = $element;
                }
            }
        }
        $result['top'] = ($data['top']['section'] ?: ($data['top']['parent'] ?: $data['top']['common']));
        $result['bottom'] = ($data['bottom']['section'] ?: ($data['bottom']['parent'] ?: $data['bottom']['common']));

        return $result;
    }

    protected function toArray(EntityObject $item): array
    {
        return [
            'id' => $item->get('ID'),
            'name' => $item->get('NAME'),
            'href' => $item->get('CODE'),
            'image' => ($item->get('DETAIL_PICTURE')
                ? $this->getImagePath([$item->get('DETAIL_PICTURE')])[$item->get('DETAIL_PICTURE')]
                : ''
            ),
            'description' => $item->get('PREVIEW_TEXT'),
            'position' => ($item->get('POSITION') && $item->get('POSITION')->getItem()
                ? $item->get('POSITION')->getItem()->get('XML_ID')
                : ''
            ),
            'section' => ($item->get('SECTION_DISPLAY') && $item->get('SECTION_DISPLAY')->getSection()
                ? $item->get('SECTION_DISPLAY')->getSection()->get('ID')
                : ''
            ),
            'brand' => ($item->get('BRAND_DISPLAY') && $item->get('BRAND_DISPLAY')->getSection()
                ? $item->get('BRAND_DISPLAY')->getSection()->get('ID')
                : ''
            ),
        ];
    }

    protected function init(array $params): void
    {
        $this->params = $this->prepareParams($params);
        $postfix = ($this->params['lang'] !== 'ru' ? '_' . $this->params['lang'] : '');
        $this->repository = new PromoBannerRepository(ConstantEntityInterface::IBLOCK_PROMO_BANNER.$postfix);
    }

    protected function prepareFilter(): array
    {
        $dateTime = DateTime::createFromPhp((new \DateTime()))->toString();
        $filter = [
            '=ACTIVE' => 'Y',
            [
                'LOGIC' => 'OR',
                ['<=ACTIVE_FROM' => $dateTime,'>=ACTIVE_TO' => $dateTime],
                ['<=ACTIVE_FROM' => $dateTime,'=ACTIVE_TO' => false],
                ['=ACTIVE_FROM' => false,'=ACTIVE_TO' => false]
            ]
        ];

        if($this->params['filter']['ids'] && count($this->params['filter']['ids']) > 0) {
            $filter['ID'] = $this->params['filter']['ids'];
        } elseif($this->params['filter']['categoryId'] && intval($this->params['filter']['categoryId']) > 0) {
            $filter[1] = [
                'LOGIC' => 'OR',
                ['SECTION_DISPLAY.VALUE' => intval($this->params['filter']['categoryId'])]
            ];
            $this->parentId = ($this->getSectionParentId($this->params['filter']['categoryId']));
            if($this->parentId > 0) {
                $filter[1][] = ['SECTION_DISPLAY.VALUE' => $this->parentId];
            }
            $filter[1][] = ['SECTION_DISPLAY.VALUE' => false, 'BRAND_DISPLAY.VALUE' => false];
            $this->isSection = true;
        } elseif($this->params['filter']['brandId'] && intval($this->params['filter']['brandId']) > 0) {
            $filter[] = [
                'LOGIC' => 'OR',
                ['BRAND_DISPLAY.VALUE' => intval($this->params['filter']['brandId'])],
                ['SECTION_DISPLAY.VALUE' => false,'BRAND_DISPLAY.VALUE' => false],
            ];
            $this->isBrand = true;
        }

        return $filter;
    }

    protected function prepareParams(array $params): array
    {
        $paramService = new ParamsServices();

        if (key_exists('city', $params)) {
            $params['city'] = $paramService->prepareIntParam('city', $params['city'], ConstantEntityInterface::MIN_CITY_VALUE);
        } else {
            $params['city'] = ConstantEntityInterface::DEFAULT_CITY_ID;
        }

        if (key_exists('lang', $params) && $params['lang']) {
            $params['lang'] = $paramService->prepareStringParams('lang', $params['lang'], ConstantEntityInterface::MIN_LANG_LENGTH);
        } else {
            $params['lang'] = ConstantEntityInterface::DEFAULT_LANG_CODE;
        }

        if (key_exists('filter', $params) && $params['filter']) {
            $filter = json_decode($params['filter'], true);
            if (json_last_error() !== 0) {
                throw new RequestBodyException('Parameter [filter] must be valid json string.');
            }
            $params['filter'] = $filter;
        }

        return $params;
    }

    protected function getSectionParentId(int $id): int
    {
        $result = SectionTable::getList([
                'filter' => ['ID' => $id],
                'select' => ['IBLOCK_SECTION_ID']]
        )->fetchObject();
        if(!$result) {
            throw new RequestBodyException('Incorrect data in the filter parameter');
        }

        return (int)$result->get('IBLOCK_SECTION_ID');
    }
}
