<?php

namespace NaturaSiberica\Api\Services\Catalog;

use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use CSearchLanguage;
use NaturaSiberica\Api\ElasticSearch\QueryBuilder;
use NaturaSiberica\Api\Elasticsearch\QueryFilter;
use NaturaSiberica\Api\Elasticsearch\Repositories\ProductsRepository;
use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Services\ParamsServices;
use NaturaSiberica\Api\Services\Search\SearchService;
use NaturaSiberica\Api\Traits\NormalizerTrait;

Loader::includeModule('search');

class ProductFilterService
{
    use NormalizerTrait;

    private QueryBuilder $builder;
    protected string     $index                  = '';
    protected int        $limit                  = 2000;
    protected int        $count                  = 0;
    protected int        $subcategoryDepth       = 2;
    protected array      $params                 = [];
    protected array      $preQueryFilter         = [];
    protected array      $filterableCategory     = [];
    protected array      $categoryDataList       = [];
    protected array      $filterablePropertyList = [];
    protected array $badgeList = [
        'exclusive'      => 1,
        'new'            => 2,
        'bestseller'     => 3,
        'vegan'          => 4,
        'professional'   => 5,
        'organic'        => 6,
        'hypoallergenic' => 7,
        'oneonethree'    => 8,
    ];
    protected bool $isNeedNullValue  = true;
    protected bool $needDiscontinued = false;

    public function index(array $params): array
    {
        $this->init($params);
        $data             = $this->getAggregationsData();
        $filter           = $this->getParamsFilter($data, $this->prepareFilterParamItemList($this->categoryDataList), $this->isNeedNullValue);
        $count = $this->count;
        if ($this->params['filter']) {
            $data   = $this->getAggregationsData($this->params['filter']);
            $data = $this->filterAggregationsData($data, $this->params['filter']);
            $filter = $this->getParamsFilter($data, $filter, true);
            $countFilter = $this->filterCategories($this->params['filter']);
            $count = $this->getCount($countFilter);
        }
        return ['count' => $count, 'list' => $filter];
    }

    protected function getParamsFilter(array $data, array $filterData, bool $isNeedNullValue = false): array
    {
        foreach ($filterData as $key => $filterItem) {
            if ($aggrsItem = $data[$filterItem['name']]) {
                $filterData[$key]['count'] = $aggrsItem['doc_count'];
                if ($filterItem['type'] === 'range') {
                    $valueRange              = $this->getRangeParam($aggrsItem[$filterItem['name']]['buckets']);
                    $filterData[$key]['min'] = $valueRange['min'];
                    $filterData[$key]['max'] = $valueRange['max'];
                } else {
                    $filterData[$key]['valueList'] = $this->getListParam(
                        $aggrsItem[$filterItem['name']]['buckets'],
                        $filterItem['valueList'],
                        $isNeedNullValue
                    );
                }
            }
        }
        return $filterData;
    }

    protected function getListParam(array $valueList, array $itemList, bool $isNeedNullValue): array
    {
        $values = [];
        foreach ($valueList as $valueItem) {
            $values[$valueItem['key']] = $valueItem['doc_count'];
        }
        $result = [];
        foreach ($itemList as $item) {
            if ($values[$item['id']]) {
                $item['count'] = $values[$item['id']];
                $result[]      = $item;
            } elseif ($isNeedNullValue) {
                $item['count'] = 0;
                $result[]      = $item;
            }
        }
        return $result;
    }

    protected function getRangeParam(array $valueList): array
    {
        $result = ['min' => 0, 'max' => 0];
        if ($valueList[0]) {
            $result['min'] = $valueList[0]['key'];
            $result['max'] = $valueList[0]['key'];
            foreach ($valueList as $item) {
                if ($item['key'] > $result['max']) {
                    $result['max'] = $item['key'];
                }
                if ($item['key'] < $result['min']) {
                    $result['min'] = $item['key'];
                }
            }
        }
        return $result;
    }

    protected function prepareFilterParamItemList(array $categoryList): array
    {
        $result = [
            $this->getPriceFilterParam(),
            $this->getCategoryFilterParam($categoryList),
            $this->getSubCategoryFilterParam($categoryList),
        ];
        $badgeList = [];
        foreach ($this->filterablePropertyList as $propertyCode => $property) {
            if($this->badgeList[$propertyCode]) {
                $badgeList[$this->badgeList[$propertyCode]] = [
                    'id'        => $property['id'],
                    'sort'      => $property['sort'],
                    'type'      => $this->getDisplayType($property['display_type']),
                    'text'      => $property['name'],
                    'name'      => strtolower($property['code']),
                    'code'      => $this->convertSnakeToCamel(strtolower($property['code'])),
                    'min'       => 0,
                    'max'       => 0,
                    'count'     => 0,
                    'valueList' => ($property['values'] ? $this->getValueList($property) : []),
                ];
            } else {
                $result[] = [
                    'id'        => $property['id'],
                    'sort'      => $property['sort'],
                    'type'      => $this->getDisplayType($property['display_type']),
                    'text'      => $property['name'],
                    'name'      => strtolower($property['code']),
                    'code'      => $this->convertSnakeToCamel(strtolower($property['code'])),
                    'min'       => 0,
                    'max'       => 0,
                    'count'     => 0,
                    'valueList' => ($property['values'] ? $this->getValueList($property) : []),
                ];
            }
        }
        if($badgeList) {
            ksort($badgeList);
            $result = array_merge($result, $badgeList);
        }

        return $result;
    }

    protected function getValueList(array $property): array
    {
        $result = [];
        if ($property['values']) {
            foreach ($property['values'] as $value) {
                if ($property['user_type'] === 'directory') {
                    $result[] = [
                        'id'    => $value['id'],
                        'value' => $value['name'],
                        'code'  => $value['code'],
                        'count' => 0,
                    ];
                } elseif ($property['type'] === 'E') {
                    $result[] = [
                        'id'          => $value['id'],
                        'value'       => $value['value'],
                        'code'        => $value['code'],
                        'parentId'    => $value['parentId'],
                        'parentValue' => $value['parentValue'],
                        'count'       => 0,
                    ];
                } else {
                    $result[] = [
                        'id'    => $value['id'],
                        'value' => $value['value'],
                        'code'  => $value['code'],
                        'count' => 0,
                    ];
                }
            }
        }
        usort($result, function ($a, $b) {
            return strcmp($a['value'], $b['value']);
        });

        return $result;
    }

    protected function getDisplayType(string $type): string
    {
        if ($type === 'A') {
            return 'range';
        } elseif (($type === 'K') || ($type === 'G') || ($type === 'H')) {
            return 'radio';
        } else {
            return 'checkbox';
        }
    }

    protected function getPriceFilterParam(): array
    {
        return [
            'id'        => 0,
            'sort'      => 1,
            'type'      => 'range',
            'text'      => 'Цена',
            'name'      => 'price',
            'code'      => 'price',
            'min'       => 0,
            'max'       => 0,
            'count'     => 0,
            'valueList' => [],
        ];
    }

    protected function getCategoryFilterParam(array $categoryList): array
    {
        $result = [
            'id'        => 1,
            'sort'      => 3,
            'type'      => 'checkbox',
            'text'      => 'Категория',
            'name'      => 'category',
            'code'      => 'category',
            'min'       => 0,
            'max'       => 0,
            'count'     => 0,
            'valueList' => [],
        ];
        foreach ($categoryList as $categoryItem) {
            if ($categoryItem['parentId'] === 0) {
                $result['valueList'][] = [
                    'id'    => $categoryItem['id'],
                    'value' => $categoryItem['name'],
                    'code'  => $categoryItem['code'],
                    'count' => 0,
                ];
            }
        }
        usort($result['valueList'], function ($a, $b) {
            return strcmp($a['value'], $b['value']);
        });
        return $result;
    }

    protected function getSubCategoryFilterParam(array $categoryList): array
    {
        $categoryIds = $this->params['categoryIds'] ? : [];

        $subcategoriesList = array_filter($categoryList, function ($categoryItem) {
            return $categoryItem['depthLevel'] == $this->subcategoryDepth;
        });

        $categoryListNames = [];

        foreach ($categoryList as $categoryItem) {
            $categoryListNames[$categoryItem['id']] = $categoryItem['name'];
        }

        $resultSubcategoriesList = [];
        if (!empty($categoryIds)) {
            $resultSubcategoriesList = array_filter($subcategoriesList, function ($categoryItem) use ($categoryIds) {
                return in_array($categoryItem['parentId'], $categoryIds);
            });
        } else {
            $resultSubcategoriesList = $subcategoriesList;
        }

        $result = [
            'id'        => 3,
            'sort'      => 4,
            'type'      => 'checkbox',
            'text'      => 'Подкатегория',
            'name'      => 'category',
            'code'      => 'subcategory',
            'min'       => 0,
            'max'       => 0,
            'count'     => 0,
            'valueList' => [],
        ];

        foreach ($resultSubcategoriesList as $categoryItem) {
            $result['valueList'][] = [
                'id'          => $categoryItem['id'],
                'value'       => $categoryItem['name'],
                'code'        => $categoryItem['code'],
                'count'       => 0,
                'parentId'    => $categoryItem['parentId'],
                'parentValue' => $categoryListNames[$categoryItem['parentId']],
            ];
        }

        usort($result['valueList'], function ($a, $b) {
            return strcmp($a['value'], $b['value']);
        });
        return $result;
    }

    public function getAggregationsData(array $filter = [])
    {
        $aggrs       = $this->getAggregationsList($filter);
        $discontinuedFilter['discontinued'] = $this->needDiscontinued;
        $discontinuedFilter['is_gift'] = false;
        $queryFilter = new QueryFilter(json_encode($discontinuedFilter));
        $productFilter = array_merge(
            ($this->preQueryFilter ? [$this->preQueryFilter] : []),
            ($queryFilter->getFilter() ?: [])
        );
        $result      = $this->builder
            ->setIndex($this->index)
            ->setLimit(0)
            ->setFilter($productFilter)
            ->setAggregation($aggrs)
            ->exec();
        $this->count = $result['hits']['total']['value'];
        if($filter) {
            $this->count = $this->getCount($filter);
        }

        return ($result['aggregations'] ? : []);
    }

    public function getCount(array $filter): int
    {
        $filter['discontinued'] = $this->needDiscontinued;
        $queryFilter = new QueryFilter(json_encode($filter));
        $productFilter = array_merge(
            ($this->preQueryFilter ? [$this->preQueryFilter] : []),
            ($queryFilter->getFilter() ?: [])
        );
        $result = $this->builder
            ->setIndex($this->index)
            ->setFilter($productFilter)
            ->setFields(['id'])
            ->exec();
        return $result['hits']['total']['value'] ?? 0;
    }

    public function getAggregationsList(array $paramFilter = []): array
    {
        $result['price']       = [
            'filter' => $this->aggregationFilter('price', $paramFilter),
            'aggs'   => [
                'price' => [
                    'terms' => [
                        'field' => 'price',
                        'size'  => $this->limit,
                    ],
                ],
            ],
        ];
        $result['category']    = [
            'filter' => $this->aggregationFilter('category_id', $paramFilter),
            'aggs'   => [
                'category' => [
                    'terms' => [
                        'field' => 'category_id',
                        'size'  => $this->limit,
                    ],
                ],
            ],
        ];

        foreach ($this->filterablePropertyList as $code => $property) {
            if ((($property['type'] === PropertyTable::TYPE_STRING) || ($property['type'] === PropertyTable::TYPE_NUMBER)) && ($property['user_type'] === '')) {
                $result[$code] = [
                    'filter' => $this->aggregationFilter($code, $paramFilter),
                    'aggs'   => [
                        $code => [
                            'terms' => [
                                'field' => $code,
                                'size'  => $this->limit,
                            ],
                        ],
                    ],
                ];
            } elseif (($property['type'] !== PropertyTable::TYPE_FILE) && ($property['user_type'] !== 'HTML')) {
                $result[$code] = [
                    'filter' => $this->aggregationFilter($code . '_id', $paramFilter),
                    'aggs'   => [
                        $code => [
                            'terms' => [
                                'field' => $code . '_id',
                                'size'  => $this->limit,
                            ],
                        ],
                    ],
                ];
            }
        }
        return $result;
    }

    public function aggregationFilter($field, $params): array
    {
        $result['bool']['filter'] = [];
        if (key_exists($field, $params)) {
            unset($params[$field]);
        }
        $result['bool']['filter'][] = ['range' => [$field => ['gt' => 0]]];
        foreach ($params as $code => $value) {
            if ($code == 'ids') {
                $result['bool']['filter'][]['ids'] = ['values' => (array)$value];
            } elseif (strpos($code, 'price') !== false) {
                $result['bool']['filter'][]['range'] = [$code => ['gte' => $value['from'], 'lte' => $value['to']]];
            } elseif (is_array($value)) {
                $result['bool']['filter'][]['terms'] = [$code => $value];
            } else {
                $result['bool']['filter'][]['term'] = [$code => $value];
            }
        }
        return $result;
    }

    public function init(array $params): void
    {
        $this->params = $this->prepareRequestParams($params);
        $this->categoryDataList = $this->getCategoryList();
        $postfix      = ($this->params['lang'] !== 'ru' ? '_' . $this->params['lang'] : '');
        $this->index  = ConstantEntityInterface::IBLOCK_CATALOG . $postfix;

        $this->builder                = new QueryBuilder();
        $this->filterableCategory     = $this->prepareFilterableCategory();
        $this->filterablePropertyList = $this->prepareFilterableProperty(
            'properties' . $postfix,
            ConstantEntityInterface::IBLOCK_CATALOG,
            array_merge([0], ($this->filterableCategory ? : []))
        );
    }

    public function getCategoryList(): array
    {
        $categories = (new CategoryService())->index([
            'city'  => $this->params['city'],
            'lang'  => $this->params['lang'],
            'limit' => 1500,
        ]);
        return $categories['list'];
    }

    public function prepareFilterableProperty(string $index, string $type, array $categoryIds): array
    {
        $properties = $this->builder->setIndex($index)->setLimit($this->limit)->setFilter([
            ['term' => ['filterable' => true]],
            ['term' => ['index_type' => $type]],
            ['terms' => ['section_id' => $categoryIds]],
        ])->exec();

        $result = [];

        foreach ($properties['hits']['hits'] as $property) {
            $result[strtolower($property['_source']['code'])] = $property['_source'];
        }
        return $result;
    }

    public function prepareFilterableCategory(): array
    {
        if ($this->params['categoryIds']) {
            $this->preQueryFilter = ['terms' => ['category_id' => (array)$this->params['categoryIds']]];
            return (array)$this->params['categoryIds'];
        } elseif ($this->params['productIds']) {
            $this->preQueryFilter = ['ids' => ['values' => (array)$this->params['productIds']]];
        } elseif ($this->params['seriesIds']) {
            $this->preQueryFilter = ['terms' => ['series_id' => (array)$this->params['seriesIds']]];
        } elseif ($this->params['brandIds']) {
            $this->preQueryFilter = ['terms' => ['brand_id' => (array)$this->params['brandIds']]];
        } elseif ($this->params['discountIds']) {
            $this->preQueryFilter = ['terms' => ['sale_id' => (array)$this->params['discountIds']]];
        } elseif ($this->params['discountList']) {
            $this->preQueryFilter = ['exists' => ['field' => 'sale_id']];
        } elseif ($this->params['collectionIds']) {
            $this->preQueryFilter = ['terms' => ['collection_id' => (array)$this->params['collectionIds']]];
        } elseif ($this->params['isNew']) {
            $this->preQueryFilter = ['term' => ['is_new' => 1]];
        } elseif ($this->params['query']) {
            $searchService = new SearchService();
            $result['bool']['filter'][] = $searchService->getSearchQuery($this->params['query'], [
                'lang' => $this->params['lang'],
                'city' => $this->params['city']
            ]);
            $this->preQueryFilter = $result;
        }
        return $this->getFilterableCategoryIds();
    }

    public function getFilterableCategoryIds(): array
    {
        $result      = [];
        $productList = $this->builder->setIndex($this->index)->setFields(['category_id'])->setFilter($this->preQueryFilter)->setLimit($this->limit)->exec();
        if ($productList['hits']['hits']) {
            foreach ($productList['hits']['hits'] as $productItem) {
                $result = array_merge($result, ($productItem['_source']['category_id'] ? : []));
            }
            return ($result ? array_values(array_unique($result)) : []);
        }
        return $result;
    }

    public function prepareRequestParams(array $params): array
    {
        $service = new ParamsServices();

        if (key_exists('city', $params)) {
            $params['city'] = $service->prepareIntParam('city', $params['city']);
        } else {
            $params['city'] = ConstantEntityInterface::DEFAULT_CITY_ID;
        }

        if (key_exists('lang', $params) && $params['lang']) {
            $params['lang'] = $service->prepareStringParams('lang', $params['lang'], 2);
        } else {
            $params['lang'] = ConstantEntityInterface::DEFAULT_LANG_CODE;
        }

        if (key_exists('categoryIds', $params) && $params['categoryIds']) {
            $params['categoryIds'] = $service->prepareListParams('categoryIds', $params['categoryIds']);
        }

        if (key_exists('productIds', $params) && $params['productIds']) {
            $params['productIds'] = $service->prepareListParams('productIds', $params['productIds']);
        }

        if (key_exists('brandIds', $params) && $params['brandIds']) {
            $this->isNeedNullValue = false;
            $params['brandIds'] = $service->prepareListParams('brandIds', $params['brandIds']);
        }

        if (key_exists('seriesIds', $params) && $params['seriesIds']) {
            $params['seriesIds'] = $service->prepareListParams('seriesIds', $params['seriesIds']);
        }

        if (key_exists('discountIds', $params) && $params['discountIds']) {
            $this->isNeedNullValue = false;
            $params['discountIds'] = $service->prepareListParams('discountIds', $params['discountIds']);
        }

        if (key_exists('collectionIds', $params) && $params['collectionIds']) {
            $params['collectionIds'] = $service->prepareListParams('collectionIds', $params['collectionIds']);
        }

        if (key_exists('isNew', $params) && $params['isNew']) {
            $this->isNeedNullValue = false;
            $params['isNew'] = $service->prepareIntParam('isNew', $params['isNew']);
        }

        if (key_exists('filter', $params) && $params['filter']) {
            $paramList = json_decode($params['filter'], true);
            if (json_last_error() !== 0) {
                throw new RequestBodyException('Parameter [filter] must be valid json string.');
            }
            $filter = [];
            if ($paramList) {
                foreach ($paramList as $code => $paramItem) {
                    $filter[$this->convertCamelToSnake($code)] = $paramItem;
                }
            }
            $params['filter'] = $filter;
        } else {
            $params['filter'] = [];
        }

        if (key_exists('query', $params) && $params['query']) {
            $params['query'] = $service->prepareKeyboard(trim($params['query']));
        } else {
            $params['query'] = '';
        }
        return $params;
    }

    protected function filterAggregationsData(array $data, array $filter): array
    {
        $categoryListById = [];
        foreach ($this->categoryDataList as $categoryItem) {
            $categoryListById[$categoryItem['id']] = $categoryItem;
        }

        $currentCategoryIds          = $filter['category_id'];
        $resultIds                   = [];
        $isOnlyRootCurrentCategories = true;
        $isOnlySubCurrentCategories  = true;

        foreach ($currentCategoryIds as $currentCategoryId) {
            $resultIds[] = $currentCategoryId;
            if ($categoryListById[$currentCategoryId]['depthLevel'] == $this->subcategoryDepth) {
                $resultIds[]                 = $categoryListById[$currentCategoryId]['parentId'];
                $isOnlyRootCurrentCategories = false;
            } else {
                $isOnlySubCurrentCategories = false;
            }
        }
        $resultAddons = [];
        foreach ($categoryListById as $categoryListItem) {
            if (in_array($categoryListItem['parentId'], $resultIds)) {
                $resultIds[] = $categoryListItem['id'];
            }
            if ($isOnlyRootCurrentCategories && $categoryListItem['depthLevel'] != $this->subcategoryDepth) {
                $resultAddons[] = $categoryListItem['id'];
            }
            if ($isOnlySubCurrentCategories && $categoryListItem['depthLevel'] == $this->subcategoryDepth) {
                $resultAddons[] = $categoryListItem['id'];
            }
        }
        $resultIds       = array_merge($resultIds, $resultAddons);
        $resultIds       = array_unique($resultIds);
        $filteredBuckets = [];

        foreach ($data['category']['category']['buckets'] as $bucketItem) {
            if (in_array($bucketItem['key'], $resultIds)) {
                $filteredBuckets[] = $bucketItem;
            }
        }
        $data['category']['category']['buckets'] = $filteredBuckets;

        return $data;
    }

    protected function filterCategories(array $filter): array
    {
        if (empty($filter['category_id'])) {
            return $filter;
        }
        $categoryIds = $filter['category_id'];
        $categoryDataList = $this->categoryDataList;
        $categoryListById = [];
        foreach ($categoryDataList as $categoryItem) {
            $categoryListById[$categoryItem['id']] = $categoryItem;
        }
        $excludeCategoryIds = [];
        foreach ($categoryIds as $categoryItemId) {
            if (in_array($categoryListById[$categoryItemId]['parentId'], $categoryIds)) {
                $excludeCategoryIds[] = $categoryListById[$categoryItemId]['parentId'];
            }
        }
        $filteredCategories = array_diff($categoryIds, $excludeCategoryIds);
        $filter['category_id'] = array_values($filteredCategories);

        return $filter;
    }
}
