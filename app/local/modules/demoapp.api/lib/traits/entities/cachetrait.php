<?php

namespace NaturaSiberica\Api\Traits\Entities;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\TaggedCache;
use Bitrix\Main\Loader;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Traits\NormalizerTrait;
use Psr\Http\Message\ServerRequestInterface;
use Bitrix\Main\Config\Option;
use CUtil;

Loader::includeModule('iblock');
Loader::includeModule('demoapp.api');

trait CacheTrait
{
    public Cache $cache;
    public TaggedCache $taggedCache;
    protected string $baseDir = 'cache';

    use InfoBlockTrait, NormalizerTrait;

    public function getCacheData(array $cacheParams): array
    {
        if($this->cache->initCache($cacheParams['cacheTTL'], $cacheParams['cacheKey'], $cacheParams['cachePath'], $this->baseDir)) {
            return $this->cache->getVars();
        }
        return [];
    }

    public function addCache(ServerRequestInterface $request = null, array $data): array
    {
        if($request) {
            $cacheParams = $this->getCacheParams($request);
            if($cacheParams) {
                if(!$this->cache->initCache($cacheParams['cacheTTL'], $cacheParams['cacheKey'], $cacheParams['cachePath'], $this->baseDir)) {
                    if($this->cache->startDataCache()) {
                        if(empty($data)) {
                            $this->cache->abortDataCache();
                        }
                        $cacheData = [
                            'createdAt' => time(),
                            'data' => $data
                        ];

                        if($cacheParams['iblockId'] && !empty($data)) {
                            $this->taggedCache->startTagCache($cacheParams['cachePath']);
                            $this->taggedCache->registerTag('iblock_id_' . $cacheParams['iblockId']);
                            $this->taggedCache->endTagCache();
                        }
                        $this->cache->endDataCache($cacheData);
                        return [
                            'createdAt' => $cacheData['createdAt'],
                            'cacheTTL' => $cacheParams['cacheTTL'],
                            'cacheKey' => md5($cacheParams['cacheKey'])
                        ];
                    }
                }
            }
        }
        return [];
    }

    public function getCacheParams(ServerRequestInterface $request): array
    {
        $route = $request->getAttribute('__route__');
        $cachePath = preg_replace(['~/v[\d]+/~', '~/{[a-zA-Z]+}~'], ['', ''], $route->getPattern());
        $cacheKey = $this->getCacheKey($request->getUri()->getPath(), $request->getQueryParams());
        $cacheEntityList = self::getCacheEntityList();

        if($cacheEntityList[$cachePath] && ($cacheParam = $this->getCacheParamList($cachePath))) {
            $this->cache = Cache::createInstance();
            $this->taggedCache = Application::getInstance()->getTaggedCache();
            return [
                'cachePath' => $cachePath,
                'cacheKey' => $this->convertSnakeToCamel($cacheKey),
                'cacheTTL' => $cacheParam['cacheTTL'],
                'iblockId' => ($cacheParam['iblockCode'] ? $this->getIblockId($cacheParam['iblockCode']) : 0),
            ];
        }
        return [];
    }

    public function getCacheKey(string $path, array $params = []): string
    {
        $pathCacheKey = preg_replace('~/api/v[\d]+/~', '', $path);
        $cacheKey = $this->convertURLToCamel($pathCacheKey);
        $paramList = [];
        if($params) {
            foreach ($params as $code => $paramItem) {
                if(preg_match("#[{}]#", $paramItem)) {
                    $paramList[$code] = $code;
                    $list = json_decode($paramItem, true);
                    if($list) {
                        ksort($list, SORT_STRING);
                        foreach ($list as $key => $item) {
                            if(is_array($item)) {
                                sort($item);
                                $paramList[$code] .= '_' . $key . '_' . implode('_', $item);
                            }
                        }
                    }

                } elseif(preg_match("#[\[\]]#", $paramItem)) {
                    $item = json_decode($paramItem, true);
                    if($item) {
                        sort($item);
                        $paramList[$code] = $code . '_' . implode('_', $item);
                    }
                } else {
                    if(preg_match('/[А-Яа-яЁё]/u', $paramItem)) {
                        $paramList[$code] = $code.'_'.CUtil::translit($paramItem, 'ru');
                    } else {
                        $paramList[$code] = $code.'_'.$paramItem;
                    }
                }
            }
            sort($paramList);
        }
        return $cacheKey.($paramList ? '_'.implode('_', $paramList) : '');
    }

    public function convertURLToCamel(string $str, bool $firstToLower = true, bool $stringToLower = false): string
    {
        if ($stringToLower) {
            $str = strtolower($str);
        }
        $camelCasedString = preg_replace_callback('/(^|_|\.|-|\/)+(.)/', function ($match) {
            return ('/' === $match[1] ? '_'.$match[2] : strtoupper($match[2]));
        }, $str);

        return $firstToLower ? lcfirst($camelCasedString): $camelCasedString;
    }

    public function getCacheParamList(string $code): array
    {
        $code = $this->convertURLToCamel($code);
        if (Option::get('demoapp.api', $code . '_cache_is_need') === 'Y') {
            return [
                'cacheTTL' => (int)Option::get('demoapp.api', $code . '_cache_TTL', 3600),
                'iblockCode' => (Option::get('demoapp.api', $code . '_cache_iblock_code') !== 'null' ? Option::get(
                    'demoapp.api',
                    $code . '_cache_iblock_code'
                ) : ''),
            ];
        }
        return [];
    }

    public static function getCacheEntityList(): array
    {
        return [
            'categories' => 'Категории',
            'products/sort' => 'Сортировка',
            'products/filter' => 'Фильтр товаров',
            'products' => 'Товары',
            'actions/promo' => 'Баннеры в листинге',
            'actions' => 'Акции',
            'banners' => 'Баннеры',
            'collections' => 'Подборки/Коллекции',
            'brands/series' => 'Серии',
            'brands' => 'Бренды',
            'cities' => 'Города',
            'stores' => 'Магазины',
            'menu/footer' => 'Меню в подвале',
            'menu/header' => 'Меню в шапке',
            'content/pages' => 'Страницы',
            'content/videotutorials' => 'Видеоуроки',
            'content/certificates' => 'Сертификаты',
            'content/blogers' => 'Блогеры'
        ];
    }


}
