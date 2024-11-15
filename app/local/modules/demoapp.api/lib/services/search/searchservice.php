<?php

namespace NaturaSiberica\Api\Services\Search;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\Model\Section;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use CSearchLanguage;
use NaturaSiberica\Api\ElasticSearch\QueryBuilder;
use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Interfaces\Services\Search\SearchServiceInterface;
use NaturaSiberica\Api\Repositories\Catalog\ProductsRepository;
use NaturaSiberica\Api\Services\Catalog\ProductService;
use NaturaSiberica\Api\Services\ParamsServices;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;

class SearchService implements SearchServiceInterface
{
    use InfoBlockTrait;

    protected const MIN_QUERY_LENGTH = 3;
    protected const LANG_LENGTH      = 2;
    protected const MIN_CITY_VALUE   = 1;
    protected const MIN_LIMIT_VALUE  = 0;
    protected const MIN_OFFSET_VALUE = 0;

    protected const DEFAULT_PRODUCTS_LIMIT    = 20;
    protected const DEFAULT_PRODUCTS_OFFSET   = 0;
    protected const DEFAULT_SUGGESTIONS_LIMIT = 6;

    protected QueryBuilder $queryBuilder;
    protected ProductsRepository $productRepository;
    protected array $params = [];
    protected array $searchableProperties = [];
    protected string $productIndex;
    protected array $searchableFields = [
        'article_list'  => 7,
        'name'          => 5,
        'category_name' => 4,
        'brand_name'    => 3,
        'excerpt'       => 2,
        'description'   => 1,
    ];

    public function __construct()
    {
        Loader::includeModule('search');
        $this->queryBuilder = new QueryBuilder();
    }

    public function index(array $params): array
    {
        $this->init($params);

        if(!isset($this->params['query']) || empty($this->params['query'])) {
            return $this->getProcessSearch();
        }

        $this->queryBuilder
            ->setIndex($this->productIndex)
            ->setLimit($this->params['limit'])
            ->setOffset($this->params['offset'])
            ->setQuery($this->getSearchQuery($this->params['query']));

        if ($aggs = $this->prepareSearchAggregations()) {
            $this->queryBuilder->setAggregation($aggs);
        }

        if ($suggest = $this->prepareSearchSuggest()) {
            $this->queryBuilder->setSuggest($suggest);
        }

        $rawData = $this->queryBuilder->exec();

        return $this->processData($rawData);
    }

    protected function init(array $params): void
    {
        $this->params = $this->prepareParams($params);

        $postfix                 = ($this->params['lang'] !== 'ru' ? '_' . $this->params['lang'] : '');
        $this->productRepository = new ProductsRepository(ConstantEntityInterface::IBLOCK_CATALOG . $postfix);
        $this->productIndex      = ConstantEntityInterface::IBLOCK_CATALOG . $postfix;
        $this->setSearchableProperties('properties' . $postfix, ConstantEntityInterface::IBLOCK_CATALOG);
    }

    protected function prepareParams(array $params): array
    {
        $paramService = new ParamsServices();

        if (key_exists('query', $params)) {
            $params['query'] = ltrim($params['query']);
            if (mb_strlen($params['query']) < self::MIN_QUERY_LENGTH) {
                unset($params['query']);
            } else {
                $params['query'] = (string)$params['query'];
            }
        }

        if (key_exists('city', $params)) {
            $params['city'] = $paramService->prepareIntParam('city', $params['city'], self::MIN_CITY_VALUE);
        } else {
            $params['city'] = ConstantEntityInterface::DEFAULT_CITY_ID;
        }

        if (key_exists('lang', $params) && $params['lang']) {
            $params['lang'] = $paramService->prepareStringParams('lang', $params['lang'], self::LANG_LENGTH);
        } else {
            $params['lang'] = ConstantEntityInterface::DEFAULT_LANG_CODE;
        }

        if (key_exists('limit', $params)) {
            $params['limit'] = $paramService->prepareIntParam('limit', $params['limit'], self::MIN_LIMIT_VALUE);
        } else {
            $params['limit'] = self::DEFAULT_PRODUCTS_LIMIT;
        }

        if (key_exists('offset', $params)) {
            $params['offset'] = $paramService->prepareIntParam('offset', $params['offset'], self::MIN_OFFSET_VALUE);
        } else {
            $params['offset'] = self::DEFAULT_PRODUCTS_OFFSET;
        }

        if (key_exists('categoryLimit', $params)) {
            $params['categoryLimit'] = $paramService->prepareIntParam('categoryLimit', $params['categoryLimit'], self::MIN_LIMIT_VALUE);
        } else {
            $params['categoryLimit'] = self::DEFAULT_SUGGESTIONS_LIMIT;
        }

        if (key_exists('brandLimit', $params)) {
            $params['brandLimit'] = $paramService->prepareIntParam('brandLimit', $params['brandLimit'], self::MIN_LIMIT_VALUE);
        } else {
            $params['brandLimit'] = self::DEFAULT_SUGGESTIONS_LIMIT;
        }

        if (key_exists('autocompleteLimit', $params)) {
            $params['autocompleteLimit'] = $paramService->prepareIntParam('autocompleteLimit', $params['autocompleteLimit'], self::MIN_LIMIT_VALUE);
        } else {
            $params['autocompleteLimit'] = self::DEFAULT_SUGGESTIONS_LIMIT;
        }

        return $params;
    }

    protected function getProcessSearch(): array
    {
        return [
            'query'        => '',
            'productList'  => ['count' => 0, 'list' => []],
            'categoryList' => ($this->getElements('products') ?? []),
            'brandList'    => ($this->getElements('brands') ?? []),
            'seriesList'    => ($this->getAttElements('brands') ?? []),
            'autocomplete' => ['count' => 0, 'list' => []],
        ];
    }

    protected function getAttElements(string $code): array
    {
        $iblockId = $this->getIblockId($code);
        $data = ElementTable::getList([
            'order' => ['SORT' => 'ASC'],
            'filter' => [
                'ACTIVE' => 'Y',
                'IBLOCK_SECTION.ACTIVE' => 'Y',
                'IBLOCK_SECTION.GLOBAL_ACTIVE' => 'Y',
                'IBLOCK_ID' => $iblockId
            ],
            'limit' => 5,
            'select' => ['ID', 'NAME', 'CODE', 'IBLOCK_SECTION_ID']
        ])->fetchAll();
        if($data) {
            $list = [];
            foreach ($data as $item) {
                $list[] = [
                    'id' => (int)$item['ID'],
                    'name' => $item['NAME'],
                    'code' => $item['CODE'],
                    'brand' => $item['IBLOCK_SECTION_ID'],
                ];
            }
            return [
                'count' => count($list),
                'list' => $list
            ];
        }
        return [];
    }

    protected function getElements(string $code): array
    {
        $iblockId = $this->getIblockId($code);
        $entity = Section::compileEntityByIblock($iblockId);
        if($entity) {
            $query = [
                'order' => ['SORT' => 'ASC'],
                'filter' => ['ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'],
                'limit' => 5,
                'select' => ['ID', 'NAME', 'CODE']
            ];
            $list = [];
            foreach ($entity::getList($query)->fetchAll() as $item) {
                $list[] = [
                    'id' => (int)$item['ID'],
                    'name' => $item['NAME'],
                    'code' => $item['CODE'],
                ];
            }
            return [
                'count' => count($list),
                'list' => $list
            ];
        }
        return [];
    }

    protected function convertKeyboardIfNeed(string $query): string
    {
        $arLang = CSearchLanguage::GuessLanguage($query);
        if (is_array($arLang) && ($arLang['from'] !== $arLang['to'])) {
            $query = CSearchLanguage::ConvertKeyboardLayout($query, $arLang['from'], $arLang['to']);
        }
        return $query;
    }

    protected function setSearchableProperties(string $index, string $type): void
    {
        $items = $this->queryBuilder->setIndex($index)->setLimit(1000)->setFilter([
            ['term' => ['searchable' => true]],
            ['term' => ['index_type' => $type]]
        ])->exec();
        if($items['hits']['hits']) {
            foreach ($items['hits']['hits'] as $itemMap) {
                $item = $itemMap['_source'];
                $propCode = '';
                if (
                    ((($item['type'] === PropertyTable::TYPE_STRING) || ($item['type'] === PropertyTable::TYPE_NUMBER))
                    && ($item['user_type'] === '' || $item['user_type'] === 'HTML'))
                    || $item['type'] === PropertyTable::TYPE_FILE
                ) {
                    $propCode = mb_strtolower($item['code']);
                } elseif($item['type'] === PropertyTable::TYPE_LIST) {
                    $propCode = mb_strtolower($item['code']) . '_value';
                } elseif (($item['user_type'] === 'directory') || $item['type'] === PropertyTable::TYPE_ELEMENT || $item['type'] === PropertyTable::TYPE_SECTION) {
                    $propCode = mb_strtolower($item['code']) . '_name';
                }

                if (! key_exists($propCode, $this->searchableFields)) {
                    $this->searchableProperties[$propCode] = 1;
                }
            }
        }
    }

    public function getSearchQuery(string $query, array $params = []): array
    {
        if($params) {
            $this->init($params);
        }
        $query = trim($query);
        $fields = array_map(
            function ($field, $boost) {
                return $field . '^' . $boost;
            },
            array_keys($this->searchableFields),
            array_values($this->searchableFields)
        );
        if($this->searchableProperties) {
            foreach ($this->searchableProperties as $property => $boost) {
                $fields[] = $property;
            }
        }
        $body = [
            'bool' => [
                'should' => [
                    [
                        'multi_match' => [
                            'query'                => $query,
                            'fields'               => $fields,
                            'type'                 => 'phrase',
                            'boost'                 => 10,
                            'slop' => 1,
                            'minimum_should_match' => 2
                        ]
                    ],
                    [
                        'multi_match' => [
                            'query'                => $query,
                            'fields'               => $fields,
                            'minimum_should_match' => 2
                        ],
                    ],
//                    [
//                        'multi_match' => [
//                            'query'                => $query,
//                            'fields'               => $fields,
//                            'type'                 => 'bool_prefix',
//                            'fuzziness'            => '1',
//                            'minimum_should_match' => '-1'
//                        ],
//                    ],
                ],
                'filter' => [
                    [
                        'term' => [
                            'is_gift' => false
                        ]
                    ]
                ]
            ],
        ];

        $data = $this->queryBuilder->setIndex($this->productIndex)->setQuery($body)->exec();

        if(!$data['hits']['hits']) {
            $body['bool']['should'][0]['multi_match']['query'] = $this->convertKeyboardIfNeed($query);
        }

        return $body;
    }

    protected function prepareSearchSuggest(): array
    {
        return [
            'completion' => [
                'text' => $this->params['query'],
                'completion' => [
                    'field' => 'completion',
                    'size' => self::DEFAULT_SUGGESTIONS_LIMIT,//($this->params['autocompleteLimit'] ?: 10),
                    'skip_duplicates' => true,
                    'fuzzy' => ['fuzziness' => 0]
                ]
            ]
        ];

    }

    protected function prepareSearchAggregations(): array
    {
        $result = [];

        if ($this->params['categoryLimit'] > 0) {
            $result['categories'] = [
                'terms' => [
                    'field' => 'category_id',
                    'size'  => $this->params['categoryLimit'],
                    'order' => [
                        '_count' => 'desc',
                    ],
                ],
                'aggs'  => [
                    'category_id'   => [
                        'terms' => [
                            'field' => 'category_id',
                        ],
                    ],
                    'category_code' => [
                        'terms' => [
                            'field' => 'category_code',
                        ],
                    ],
                    'category_name' => [
                        'terms' => [
                            'field' => 'category_name.keyword',
                        ],
                    ],
                ],
            ];
        }
        if ($this->params['brandLimit'] > 0) {
            $result['brands'] = [
                'terms' => [
                    'field' => 'brand_id',
                    'size'  => $this->params['brandLimit'],
                    'order' => [
                        '_count' => 'desc',
                    ],
                ],
                'aggs'  => [
                    'brand_id'   => [
                        'terms' => [
                            'field' => 'brand_id',
                        ],
                    ],
                    'brand_code' => [
                        'terms' => [
                            'field' => 'brand_code',
                        ],
                    ],
                    'brand_name' => [
                        'terms' => [
                            'field' => 'brand_name.keyword',
                        ],
                    ],
                ],
            ];

            $result['series'] = [
                'terms' => [
                    'field' => 'series_id',
                    'size'  => $this->params['brandLimit'],
                    'order' => [
                        '_count' => 'desc',
                    ],
                ],
                'aggs'  => [
                    'series_id'   => [
                        'terms' => [
                            'field' => 'series_id',
                        ],
                    ],
                    'series_code' => [
                        'terms' => [
                            'field' => 'series_code',
                        ],
                    ],
                    'series_name' => [
                        'terms' => [
                            'field' => 'series_name.keyword',
                        ],
                    ],
                ],
            ];
        }


        return $result;
    }

    /**
     * @param array $rawData
     *
     * @return array
     * @throws RequestBodyException
     */
    protected function processData(array $rawData): array
    {
        if($rawData['hits']['total']['value'] === 0) {
            return $this->getProcessSearch();
        }
        return [
            'query'        => trim($this->params['query']),
            'productList'  => $this->processProducts($rawData),
            'categoryList' => $this->processSuggestions('category', $rawData['aggregations']['categories']['buckets'] ?? []),
            'brandList'    => $this->processSuggestions('brand', $rawData['aggregations']['brands']['buckets'] ?? []),
            'seriesList'    => $this->processSuggestions('series', $rawData['aggregations']['series']['buckets'] ?? []),
            'autocomplete' => $this->processAutocompleteSuggestions($rawData ?? []),
        ];
    }

    /**
     * @param array $rawData
     *
     * @return array
     * @throws RequestBodyException
     */
    protected function processProducts(array $rawData): array
    {
        $list   = (new ProductService())->index([
            'filter' => json_encode(['ids' => array_column($rawData['hits']['hits'], '_id')]),
            'city'   => $this->params['city'],
            'lang'   => $this->params['lang'],
            'sort' => 'default'
        ], $rawData);

        return [
            'count' => $rawData['hits']['total']['value'] ? : 0,
            'list'  => $list['list'],
        ];
    }

    /**
     * @param string $code
     * @param array  $rawData
     *
     * @return array
     */
    protected function processSuggestions(string $code, array $rawData): array
    {
        $result = [
            'count' => count($rawData),
            'list'  => array_reduce(
                $rawData, function ($acc, $item) use ($code) {
                $itemId = $item['key'];
                foreach ($item[$code . '_id']['buckets'] as $index => $data) {
                    if ($data['key'] === $itemId) {
                        $acc[] = [
                            'id'   => (int)$item[$code . '_id']['buckets'][$index]['key'],
                            'code' => $item[$code . '_code']['buckets'][$index]['key'],
                            'name' => $item[$code . '_name']['buckets'][$index]['key'],
                        ];
                        return $acc;
                    }
                }
            }, []
            ),
        ];

        return $result;
    }

    /**
     * @param array $rawData
     *
     * @return array
     */
    protected function processAutocompleteSuggestions(array $rawData): array
    {
        $result = ['count' => 0, 'list' => []];
        if($rawData) {
            $suggestList = [];
            foreach ($rawData['suggest'] as $suggest) {
                if($suggestValue = $this->processAutocompleteSuggest($suggest)) {
                    $suggestList = array_merge($suggestList, $suggestValue);
                }
            }
            $result = [
                'count' => count($suggestList),
                'list'  => $suggestList,
            ];
        }

        return $result;
    }

    protected function processAutocompleteSuggest(array $suggestList): array
    {

        $result = [];
        foreach ($suggestList as $suggest) {
            if($suggest['options']) {
                foreach ($suggest['options'] as $option) {
                    $result[] = $option['_source']['name'];
                }
            }
        }
        return $result;
    }
}
