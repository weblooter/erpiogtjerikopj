<?php

namespace NaturaSiberica\Api\Services\Catalog;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use CSearchLanguage;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\ElasticSearch\ElasticSearchService;
use NaturaSiberica\Api\ElasticSearch\QueryBuilder;
use NaturaSiberica\Api\ElasticSearch\QueryFilter;
use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Helpers\Catalog\ProductsHelper;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Elasticsearch\Repositories\ProductsRepository;
use CIBlockElement;
use NaturaSiberica\Api\Interfaces\ModuleInterface;
use NaturaSiberica\Api\Interfaces\Services\Catalog\ProductServiceInterface;
use NaturaSiberica\Api\Repositories\Catalog\SortRepository;
use NaturaSiberica\Api\Services\ParamsServices;
use NaturaSiberica\Api\Services\Search\SearchService;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;
use NaturaSiberica\Api\Traits\NormalizerTrait;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ServerRequestFactory;

Loader::includeModule('iblock');
Loader::includeModule('search');

Loc::loadMessages(__FILE__);

class ProductService implements ProductServiceInterface
{
    use NormalizerTrait, InfoBlockTrait;

    protected ProductsRepository $repository;
    protected OfferService       $offers;
    protected QueryBuilder       $queryBuilder;
    protected string             $propertyIndex = '';
    protected string             $catalogIndex  = '';
    protected array              $params           = [];
    public static bool           $needDiscontinued = false;
    public int                   $iblockId;
    public int                  $offerIblockId;

    public function __construct()
    {
        $this->iblockId = $this->getIblockId(ConstantEntityInterface::IBLOCK_CATALOG);
        $this->offerIblockId = $this->getIblockId(ConstantEntityInterface::IBLOCK_OFFER);
    }

    /**
     * Список свойств элемента
     *
     * @var array|string[]
     */
    protected array $propertyList = [];
    /**
     * Список запрашиваемых полей элемента
     *
     * @var array|string[]
     */
    protected array $source = [];
    /**
     * Список основных полей элемента
     *
     * @var array|string[]
     */
    protected array $fieldList = ['id', 'code', 'name', 'image'];
    /**
     * Список обязательных полей элемента
     *
     * @var array|string[]
     */
    protected array $optionList = [
        'article',
        'images',
        'category',
        'brand',
        'series',
        'application',
        'composition',
        'ingredients',
        'certificates',
        'color',
    ];
    /**
     * Список полей элемента, которые относятся к лейблам
     *
     * @var array|string[]
     */
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

    protected array $searchableFields = [
        'name'          => 3,
        'category_name' => 2,
        'brand_name'    => 1.5,
        'description'   => 1.25,
    ];

    protected function getResult(int $count = 0, array $result = []): array
    {
        return [
            'pagination' => [
                'limit'  => ($this->params['limit'] ? : ConstantEntityInterface::DEFAULT_ELEMENT_COUNT),
                'offset' => ($this->params['offset'] ? : ConstantEntityInterface::MIN_OFFSET_VALUE),
                'total'  => ($count ?: 0),
            ],
            'list'       => ($result ?: []),
        ];
    }

    /**
     * Получает список элементов
     *
     * @param array $params
     *
     * @return array
     * @throws Exception
     */
    public function index(array $params, array $list = []): array
    {
        $result = [];

        if ($params['is_cart']) {
            $this->fieldList[] = 'thumbnail';
        }

        $this->init($params, 'show_list');
        if(!$list) {
            $list  = $this->getElementData();
        }
        $count = $this->getTotalCount();

        if ($count <= $this->params['offset']) {
            return $this->getResult($count, $result);
        } elseif ($list['hits']['hits']) {
            $data = [];
            $productList = $this->getList($list, 'show_list');
            foreach ($productList as $product) {
                $data[] = ProductsHelper::prepareDetailImagesForVersion($this->iblockId, $product);
            }

            return $this->getResult($list['hits']['total']['value'], $data);
        }

        return $this->getResult(0, $result);
    }

    /**
     * Получает информацию об одном элементе
     *
     * @param string $code
     * @param array  $params
     *
     * @return array
     * @throws Exception
     */
    public function get(string $code, array $params): array
    {
        $this->init($params, 'show_detail');
        $this->fieldList = array_merge($this->fieldList, ['excerpt', 'description']);
        $this->source    = array_merge($this->source, ['seo_data']);

        $this->params['filter'] = json_encode(['code' => $code, 'is_gift' => false]);
        $list                   = $this->getElementData();

        if ($list['hits']['hits']) {
            $item = current($list['hits']['hits']);
            CIBlockElement::CounterInc($item['_id']);

            $data = current($this->getList($list, 'show_detail'));

            return ProductsHelper::prepareDetailImagesForVersion($this->iblockId, $data);
        } else {
            throw new Exception(Loc::getMessage('err_product_not_found'), StatusCodeInterface::STATUS_NOT_FOUND);
        }
    }

    protected function getList(array $list, string $display = 'show_list'): array
    {
        $result = [];

        $offerList = $this->offers->index([
            'city'   => $this->params['city'],
            'lang'   => $this->params['lang'],
            'filter' => json_encode(['cml2_link' => array_column($list['hits']['hits'], '_id')]),
        ], $display);

        foreach ($list['hits']['hits'] as &$item) {
            $offers = $images = [];
            if ($offerList[$item['_id']]) {
                $offers = $this->sortingSkuList($offerList[$item['_id']]);
                foreach ($offers as &$offer) {
                    if(!$item['_source']['image']) {
                        $item['_source']['image'] = ($offer['images'][0] ?: null);
                    }
                    if ($offer['images']) {
                        $images = array_merge($images, $offer['images']);
                        ProductsHelper::prepareDetailImagesForVersion($this->iblockId, $offer);
                        unset($offer['image']);
                    }
                }
            }

            if($item['_source']['images']) {
                $images = $item['_source']['images'];
            }

            if($display === 'show_list') {
                $result[] = array_merge(
                    $this->getFieldList($item['_source']), $this->getPropertyList($item['_source']),
                    ['images' => ($images ? : null)],
                    ['offerList' => $offers]
                );
            } else {
                $result[] = array_merge(
                    $this->getFieldList($item['_source']),
                    $this->getPropertyList($item['_source']),
                    ['images' => ($images ?: null)],
                    ['offerList' => $offers],
                    ['seoData' => $item['_source']['seo_data']]
                );
            }
        }

        return $result;
    }

    protected function sortingSkuList(array $data, string $key = 'price'): array
    {
        usort($data, function ($a, $b) use ($key) {
            return $a[$key] <=> $b[$key];
        });
        return $data;
    }

    /**
     * Получает список основных полей элемента
     *
     * @param array $item
     *
     * @return array
     */
    protected function getFieldList(array $item): array
    {
        $result = [];
        foreach ($this->fieldList as $fieldItem) {
            if (getType($item[$fieldItem]) === 'string') {
                $result[$this->convertSnakeToCamel($fieldItem)] = html_entity_decode($item[$fieldItem]);
            } else {
                $result[$this->convertSnakeToCamel($fieldItem)] = $item[$fieldItem];
            }
        }
        return $result;
    }

    /**
     * Получает список свойств элемента
     *
     * @param array $item
     *
     * @return array
     */
    protected function getPropertyList(array $item): array
    {
        $result = [];
        foreach ($this->propertyList as $property) {
            $code = $this->convertSnakeToCamel($property['code']);
            if ($property['type'] === 'L' || ($property['type'] === 'S' && $property['user_type'] === 'directory') || $property['type'] === 'G' || $property['type'] === 'E') {
                if (in_array($property['code'], $this->optionList)) {
                    if ($property['code'] === 'color') {
                        $result[$code] = ($this->collectFields($item, $property['code']) ?? null);
                    } elseif ($property['code'] === 'category') {
                        $categoryList = $this->collectFields($item, $property['code']);
                        if ($categoryList) {
                            uasort($categoryList, function ($a, $b) {
                                return ($a['sort'] > $b['sort']);
                            });
                        }
                        $result[$code] = array_values($categoryList);
                    } else {
                        $result[$code] = $this->collectFields($item, $property['code']);
                    }
                } elseif ($this->badgeList[$property['code']]) {
                    if ($item[$property['code'] . '_id']) {
                        $result['badgeList'][$this->badgeList[$property['code']]] = [
                            'id'   => $item[$property['code'] . '_id'],
                            'code' => $property['code'],
                            'name' => $property['name'],
                        ];
                    }
                } else {
                    $result['propertyList'][$code] = $this->collectFields($item, $property['code']);
                }
            } elseif ($property['type'] === 'S' || $property['type'] === 'N' || $property['type'] === 'F') {
                if (in_array($property['code'], $this->optionList)) {
                    $result[$code] = (getType($item[$property['code']]) === 'string' ? html_entity_decode(
                        $item[$property['code']]
                    ) : $item[$property['code']]);
                } elseif ($this->badgeList[$property['code']]) {
                    if ($item[$property['code'] . '_id']) {
                        $result['badgeList'][$this->badgeList[$property['code']]] = [
                            'id'   => $item[$property['code'] . '_id'],
                            'code' => $property['code'],
                            'name' => $property['name'],
                        ];
                    }
                } else {
                    $result['propertyList'][$code] = (getType($item[$property['code']]) === 'string' ? html_entity_decode(
                        $item[$property['code']]
                    ) : $item[$property['code']]);
                }
            }
        }
        ksort($result['badgeList']);
        $result['badgeList'] = array_values($result['badgeList']);
        return $result;
    }

    /**
     * Собирает массив характеристик поля
     *
     * @param array  $item
     * @param string $code
     *
     * @return array
     */
    protected function collectFields(array $item, string $code): array
    {
        $result = [];
        $list   = $this->collectsKeysFieldByCode($item, $code);
        foreach ($list as $field => $value) {
            $keys    = explode('_', $field);
            $nameKey = end($keys);
            if (is_array($value) && $nameKey !== 'video') {
                foreach ($value as $key => $val) {
                    $result[$key][$nameKey] = (getType($val) === 'string' ? html_entity_decode($val) : $val);
                }
            } elseif (in_array($code, $this->optionList)) {
                $result[$nameKey] = (getType($value) === 'string' ? html_entity_decode($value) : $value);
            } else {
                $result[0][$nameKey] = (getType($value) === 'string' ? html_entity_decode($value) : $value);
            }
        }

        return $result;
    }

    /**
     * Собирает список ключей поля по коду этого поля
     *
     * @param array  $item
     * @param string $code
     *
     * @return array
     */
    protected function collectsKeysFieldByCode(array $item, string $code): array
    {
        $keys         = array_keys($item);
        $matchingKeys = preg_grep('/^' . $code . '_+/', $keys);
        return array_intersect_key($item, array_flip($matchingKeys));
    }

    /**
     * Получает список элементов по фильтру
     *
     * @return array
     */
    protected function getElementData(): array
    {
        return $this->repository->all(
            $this->catalogIndex,
            $this->getQueryFilter($this->params),
            $this->getSort($this->params),
            array_merge($this->fieldList, $this->source),
            $this->params['limit'],
            $this->params['offset']
        );
    }

    protected function getTotalCount(): int
    {
        $data = $this->repository->count(
            $this->catalogIndex,
            $this->getQueryFilter($this->params),
        );

        return ($data ? : 0);
    }

    /**
     * Получает сортировку запроса
     *
     * @param array $params
     *
     * @return array|\string[][][]
     */
    protected function getSort(array $params): array
    {
        $isNew = false;
        if($params['filter']) {
            $filter = json_decode($params['filter'], true);
            if($filter && $filter['isNew']) {
                $isNew = true;
            }
        }

        $sort = ['availability' => ['order' => 'desc']];
        if($params['sort'] === 'brand_score') {
            $sort['sort_for_brand'] = ['order' => 'asc'];
        } else if ($params['sort'] === 'brand') {
            $order = $params['order'] ?: 'asc';
            $sort['brand_name.keyword'] = ['order' => $order];
            $sort['series_name.keyword'] = ['order' => $order];
            $sort['product_type_value.keyword'] = ['order' => $order];
            $sort['create_date'] = ['order' => 'desc'];
            unset($sort['availability']);
        } else if ($params['sort'] === 'productType') {
            $order = $params['order'] ?: 'asc';
            $sort['product_type_value.keyword'] = ['order' => $order];
            $sort['brand_name.keyword'] = ['order' => $order];
            $sort['series_name.keyword'] = ['order' => $order];
            $sort['create_date'] = ['order' => 'desc'];
            unset($sort['availability']);
        } else if ($params['query'] && $params['sort'] === 'popular') {
            $sort['popular'] = ['order' => 'desc'];
        } elseif ($params['sort'] === 'default') {
            $sort['_score'] = ['order' => 'desc'];
            $sort['price'] = ['order' => 'desc'];
        } elseif ($params['sort'] && $params['order']) {
            $sort[$params['sort']] = ['order' => $params['order']];
            if($params['sort'] === 'new_id') {
                $sort['create_date'] = ['order' => 'desc'];
            }
            if ($params['sort'] !== 'price') {
                $sort['price'] = ['order' => 'desc'];
            }
        } else {
            if($isNew) {
                $sort['create_date'] = ['order' => 'desc'];
            }
            $sort['price'] = ['order' => 'desc'];
        }

        return $sort;
    }

    /**
     * Получает фильтр запроса
     *
     * @param array $params
     *
     * @return array
     */
    protected function getQueryFilter(array $params): array
    {
        $result = [];
        if ($this->params['query']) {
            $searchService              = new SearchService();
            $result['bool']['must'][] = $searchService->getSearchQuery($this->params['query'], $this->params);
        }
        if($params['filter']) {
            $filterQuery                = new QueryFilter($params['filter']);
            $result['bool']['must'][] = $filterQuery->exec();
        }

        return $result;
    }

    /**
     * Осуществляет инициализацию основных сущностей
     *
     * @param array  $params
     * @param string $display
     *
     * @return void
     * @throws RequestBodyException
     */
    protected function init(array $params, string $display): void
    {
        $this->params = $this->prepareParams($params, $display);
        $postfix      = ($this->params['lang'] !== 'ru' ? '_' . $this->params['lang'] : '');

        $this->repository = new ProductsRepository();
        $this->offers     = new OfferService();

        $this->queryBuilder = new QueryBuilder();
        $this->catalogIndex = ConstantEntityInterface::IBLOCK_CATALOG . $postfix;

        $indices  = (new ElasticSearchService())->getClient()->indices();
        $isExists = $indices->exists(['index' => $this->catalogIndex])->asBool();

        if (! $isExists) {
            throw new HttpNotFoundException(ServerRequestFactory::createFromGlobals());
        }

        $this->propertyIndex  = 'properties' . $postfix;
        $this->propertyList   = $this->preparePropertyList($display);
        $this->propertyList[] = [
            'name'      => 'Категория',
            'code'      => 'category',
            'type'      => 'G',
            'user_type' => '',
        ];
        $this->source         = $this->prepareSourceList(($this->propertyList ? : []));
    }

    /**
     * Подготавливает переданные параметры
     *
     * @param array  $params
     * @param string $display
     *
     * @return array
     * @throws RequestBodyException
     */
    protected function prepareParams(array $params, string $display): array
    {
        $paramService = new ParamsServices();

        if (key_exists('city', $params)) {
            $params['city'] = $paramService->prepareIntParam('city', $params['city']);
        } else {
            $params['city'] = ConstantEntityInterface::DEFAULT_CITY_ID;
        }

        if (key_exists('lang', $params) && $params['lang']) {
            $params['lang'] = $paramService->prepareStringParams('lang', $params['lang'], 2);
        } else {
            $params['lang'] = ConstantEntityInterface::DEFAULT_LANG_CODE;
        }

        if (key_exists('limit', $params)) {
            $params['limit'] = $paramService->prepareIntParam('limit', $params['limit']);
        } else {
            $params['limit'] = ConstantEntityInterface::DEFAULT_ELEMENT_COUNT;
        }

        if (key_exists('offset', $params) && $params['offset']) {
            $params['offset'] = $paramService->prepareIntParam('offset', $params['offset']);
        } else {
            $params['offset'] = 0;
        }

        if (key_exists('sort', $params) && $params['sort']) {
            $sortCodeList = (new SortRepository())->getPossibleValues();
            if (! in_array($params['sort'], $sortCodeList) && $params['sort'] !== 'brand_score') {
                throw new RequestBodyException('Parameter [sort] must be one of the valid ones.');
            }
            if (key_exists('order', $params) && $params['order']) {
                if (! in_array($params['order'], ['asc', 'desc'])) {
                    throw new RequestBodyException('Parameter [order] must be one of the valid ones.');
                }
            }
        }

        if (key_exists('filter', $params) && $params['filter']) {
            $filter = json_decode($params['filter'], true);
            if (json_last_error() !== 0) {
                throw new RequestBodyException('Parameter [filter] must be valid json string.');
            }
            if($display === 'show_list') {
                if(!self::$needDiscontinued) {
                    $filter['discontinued'] = self::$needDiscontinued;
                }
                if($params['is_cart'] != 1) {
                    $filter['is_gift'] = false;
                }
                $filter = $this->filterCategories($filter, $params);
                $params['filter'] = json_encode($filter);
            }
        } else {
            $params['filter'] = '';
        }

        if (key_exists('query', $params) && $params['query']) {
            $params['query'] = trim($params['query']);
        }

        return $params;
    }

    protected function convertKeyboardIfNeed(string $query): string
    {
        $arLang = CSearchLanguage::GuessLanguage($query);

        if (is_array($arLang) && ($arLang['from'] !== $arLang['to'])) {
            $query = CSearchLanguage::ConvertKeyboardLayout($query, $arLang['from'], $arLang['to']);
        }
        return $query;
    }

    /**
     * Подготавливает список свойств
     *
     * @param string $display
     *
     * @return array
     */
    protected function preparePropertyList(string $display): array
    {
        return $this->repository->getPropertyNameList(
            $this->propertyIndex,
            $this->catalogIndex,
            $display
        );
    }

    protected function prepareSearchQuery(): array
    {
        $isWordPrefix   = false;
        $isPhrasePrefix = false;

        $queryWords = explode(' ', $this->params['query']);
        if ($queryWords[count($queryWords) - 1] !== '') {
            if (count($queryWords) > 1) {
                $isPhrasePrefix = true;
            } else {
                $isWordPrefix = true;
            }
        };

        return $isWordPrefix ? $this->getSearchQueryForWord($this->params['query']) : $this->getSearchQueryForPhrase(
            $this->params['query'],
            $isPhrasePrefix
        );
    }

    protected function getSearchQueryForWord(string $query): array
    {
        $query = trim($query);
        $fields = array_map(
            function ($field, $boost) {
                return $field . '^' . $boost;
            },
            array_keys($this->searchableFields),
            array_values($this->searchableFields)
        );

        $result = [
            'bool' => [
                'should' => [
//                    [
//                        'multi_match' => [
//                            'query'                => $query,
//                            'fields'               => ,
//                            'fuzziness'            => 'auto',
//                            'minimum_should_match' => '-1',
//                            'boost'                => 2,
//                            'prefix_length'        => 2,
//                        ],
//                    ],
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
                    ]
                ],
            ],
        ];

        foreach (array_keys($this->searchableFields) as $field) {
            $result['bool']['should'][] = [
                'prefix' => [
                    $field => $query,
                ],
            ];
        }

        return $result;
    }

    protected function getSearchQueryForPhrase(string $query, bool $isPhrase): array
    {
        $query  = trim($query);
        $fields = array_map(
            function ($field, $boost) {
                return $field . '^' . $boost;
            },
            array_keys($this->searchableFields),
            array_values($this->searchableFields)
        );
        foreach ($this->prepareSearchablePropertyList() as $property) {
            if (! key_exists($property, $this->searchableFields)) {
                $fields[] = $property;
            }
        }

        $result = [
            'bool' => [
                'should' => [
                    [
                        'multi_match' => [
                            'query'  => $query,
                            'fields' => $fields,
                            'type'   => $isPhrase ? 'phrase_prefix' : 'phrase',
                            'boost'  => 2,
                        ],
                    ],
                    [
                        'multi_match' => [
                            'query'                => $query,
                            'fields'               => $fields,
                            'type'                 => $isPhrase ? 'bool_prefix' : 'best_fields',
                            'fuzziness'            => 'auto',
                            'minimum_should_match' => '-1',
                            'boost'                => 1,
                        ],
                    ],
                ],
            ],
        ];

        return $result;
    }

    protected function prepareSearchablePropertyList(): array
    {
        return $this->repository->getSearchablePropertyList(
            $this->propertyIndex,
            $this->catalogIndex,
        );
    }

    /**
     * Подготавливает список запрашиваемых полей
     *
     * @param array $propertyList
     *
     * @return array
     */
    protected function prepareSourceList(array $propertyList): array
    {
        $source = [];
        if ($propertyList) {
            foreach ($propertyList as $propertyItem) {
                if (($propertyItem['type'] === 'S' && $propertyItem['user_type'] !== 'directory') || ($propertyItem['type'] === 'N') || ($propertyItem['type'] === 'F')) {
                    $source[] = $propertyItem['code'];
                } else {
                    $source[] = $propertyItem['code'] . '_*';
                }
            }
        }
        return $source;
    }

    /**
     * Убирает из фильтра родительский раздел, если есть дочерний.
     *
     * @param array $filter
     * @param array $params
     *
     * @return array
     */
    protected function filterCategories(array $filter, array $params): array
    {
        if (empty($filter['categoryId'])) {
            return $filter;
        }
        $categoryIds = $filter['categoryId'];
        $categoryDataList = $this->getCategoryList($params);
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
        $filter['categoryId'] = array_values($filteredCategories);
        return $filter;
    }

    /**
     * Получает полный список категорий.
     *
     * @param array $params
     *
     * @return array
     */
    public function getCategoryList(array $params): array
    {
        $categories = (new CategoryService())->index([
            'city'  => $params['city'],
            'lang'  => $params['lang'],
            'limit' => 1500,
        ]);
        return $categories['list'];
    }
}
