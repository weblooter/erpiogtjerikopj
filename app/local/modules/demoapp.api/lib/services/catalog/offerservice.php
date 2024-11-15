<?php

namespace NaturaSiberica\Api\Services\Catalog;

use NaturaSiberica\Api\Elasticsearch\QueryFilter;
use NaturaSiberica\Api\Elasticsearch\Repositories\ProductsRepository;
use NaturaSiberica\Api\Exceptions\RequestBodyException;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Services\ParamsServices;
use NaturaSiberica\Api\Traits\NormalizerTrait;

class OfferService
{
    use NormalizerTrait;

    protected ProductsRepository $repository;
    protected string $propertyIndex = '';
    protected string $catalogIndex = '';
    protected array $params = [];
    /**
     * Список свойств элемента
     * @var array|string[]
     */
    protected array $propertyList = [];
    /**
     * Список запрашиваемых полей элемента
     * @var array|string[]
     */
    protected array $source = [];
    /**
     * Список основных полей элемента
     * @var array|string[]
     */
    protected array $fieldList = ['id', 'name', 'price'];
    /**
     * Список полей свойства, используемые для выбора торгового предложения
     * @var array|string[]
     */
    protected array $optionList = ['quantity', 'volume', 'weight', 'size'];

    /**
     * Получает список элементов
     *
     * @param array  $params
     * @param string $display
     * @param bool   $onlyIds
     *
     * @return array
     * @throws RequestBodyException
     */
    public function index(array $params, string $display, bool $onlyIds = false): array
    {
        $result = [];
        $this->init($params, $display);

        if ($onlyIds) {
            return $this->getProductIds();
        }

        $list = $this->getElementData();

        if($list) {
            $isProductKey = false;
            if($params['filter']) {
                $filter = json_decode($params['filter'], true);
                if(key_exists('cml2_link', $filter)) {
                    $isProductKey = true;
                }
            }

            foreach ($list as $item) {
                if($isProductKey) {
                    $result[$item['_source']['cml2_link']][] = $this->prepareElement($item['_source']);
                } else {
                    $result[] = $this->prepareElement($item['_source']);
                }
            }
        }

        return $result;
    }

    /**
     * Подготавливает массив полей элемента
     *
     * @param array $item
     *
     * @return array
     */
    protected function prepareElement(array $item): array
    {
        return array_merge(
            $this->getFieldList($item),
            ['images' => ($this->getImageList($item) ?: null)],
            ['optionList' => ($this->getOptionList($item) ?: null)],
            ['propertyList' => $this->getPropertyList($item)],
            ['warehouseList' => $this->getWarehouse($item)],
            ['shopList' => $this->getShops($item)],
            ['seoData' => $item['seo_data']],
        );
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
            $result[$this->convertSnakeToCamel($fieldItem)] = $item[$fieldItem];
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
        $exclude = ['unit'];
        $result = [];
        foreach ($this->propertyList as $property) {
            if(!in_array($property['code'], $this->optionList) && !in_array($property['code'], $exclude)) {
                $code = $this->convertSnakeToCamel($property['code']);
                if(
                    $property['type'] === 'L'
                    || ($property['type'] === 'S' && $property['user_type'] === 'directory')
                    || $property['type'] === 'G'
                    || $property['type'] === 'E'
                ) {
                    $result[$code] = $this->collectFields($item, $property['code']);
                } elseif(
                    $property['type'] === 'S'
                    || $property['type'] === 'N'
                    || $property['type'] === 'F'
                ) {
                    $result[$code] = $item[$property['code']];
                }
            }
        }
        return $result;
    }

    /**
     * Получает поля свойства, используемые для выбора торгового предложения
     *
     * @param array $item
     *
     * @return array
     */
    protected function getOptionList(array $item): array
    {
        foreach ($this->propertyList as $property) {
            if(in_array($property['code'], $this->optionList)) {
                if($property['type'] === 'L' && $item[$property['code'].'_value']) {
                    return [
                        'id' => $item[$property['code'].'_id'],
                        'name' => $property['name'],
                        'code' => $item[$property['code'].'_code'],
                        'value' => $item[$property['code'].'_value'] .' '. $item['unit_value'],
                    ];
                } else if(($property['type'] === 'N' || $property['type'] === 'S') && $item[$property['code']]) {
                    return [
                        'id' => $item[$property['code']],
                        'name' => $property['name'],
                        'code' => $property['code'],
                        'value' => $item[$property['code']] .' '. $item['unit_value'],
                    ];
                }
            }
        }
        return [];
    }

    protected function getImageList(array $item)
    {
        $images = [];
        foreach ($item as $code => $value) {
            if(strpos($code, 'images') !== false) {
                $images[$code] = $value;
            }
        }
        ksort($images);
        return array_values($images);
    }

    /**
     * Получает список магазинов
     *
     * @param array $item
     *
     * @return array
     */
    protected function getShops(array $item): array
    {
        $result = [];
        if($item['shops']['city_'.$this->params['city']]) {
            foreach ($item['shops']['city_'.$this->params['city']] as $key => $shopList) {
                foreach ($shopList as $shopFieldCode => $shopFieldValue) {
                    $result[$key][$this->convertSnakeToCamel($shopFieldCode)] = $shopFieldValue;
                }
            }
        }
        return $result;
    }

    protected function getWarehouse(array $item): array
    {
        $result = [];
        if($item['warehouses']) {
            foreach ($item['warehouses'] as $key => $list) {
                foreach ($list as $fieldCode => $fieldValue) {
                    $code = $this->convertSnakeToCamel($fieldCode);
                    $result[$key][$code] = $fieldValue;
                }
            }
        }
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
        $list = $this->collectsKeysFieldByCode($item, $code);
        foreach ($list as $field => $value) {
            $keys = explode('_', $field);
            if(is_array($value)) {
                foreach ($value as $key => $val) {
                    $result[$key][end($keys)] = $val;
                }
            } else {
                $result[0][end($keys)] = $value;
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
        $keys = array_keys($item);
        $matchingKeys = preg_grep('/^'.$code.'_+/', $keys);
        return array_intersect_key($item, array_flip($matchingKeys));
    }

    /**
     * Получает список элементов по фильтру
     *
     * @return array
     */
    protected function getElementData(): array
    {
        $data = $this->repository->all(
            $this->catalogIndex,
            $this->getQueryFilter($this->params),
            ['price' => ['order' => 'desc']],
            array_merge($this->fieldList, $this->source, ['cml2_link', 'shops', 'warehouses', 'seo_data', 'images_*']),
            $this->getCount(),
            0
        );
        return $data['hits']['hits'];
    }

    /**
     * Получает список id товаров, привязанных к торговым предложениям
     *
     * @return array
     */
    public function getProductIds(): array
    {
        $data = $this->repository->all(
            $this->catalogIndex,
            $this->getQueryFilter($this->params),
            [],
            ['cml2_link'],
            $this->getCount(),
            0
        );
        $hits = $data['hits']['hits'];

        if (!empty($hits)) {
            $sourceList = array_column($hits, '_source');
            return array_column($sourceList, 'cml2_link');
        }

        return [];
    }

    /**
     * Получает количество элементов по фильтру
     *
     * @return int
     */
    protected function getCount(): int
    {
        return $this->repository->count(
            $this->catalogIndex,
            $this->getQueryFilter($this->params)
        );
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
        $queryFilter = new QueryFilter($params['filter']);
        return $queryFilter->exec();
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
        $this->params = $this->prepareParams($params);
        $postfix = ($this->params['lang'] !== 'ru' ? '_' . $this->params['lang'] : '');

        $this->repository = new ProductsRepository();
        $this->catalogIndex = ConstantEntityInterface::IBLOCK_OFFER.$postfix;
        $this->propertyIndex = 'properties'.$postfix;
        $this->propertyList = $this->preparePropertyList($display);
        $this->source = $this->prepareSourceList(($this->propertyList ?: []));
    }

    /**
     * Подготавливает переданные параметры
     *
     * @param array $params
     *
     * @return array
     * @throws RequestBodyException
     */
    protected function prepareParams(array $params): array
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

        if (key_exists('ids', $params) && $params['ids']) {
            $params['ids'] = $paramService->prepareListParams('ids', $params['ids']);
        }

        if (key_exists('filter', $params) && $params['filter']) {
            json_decode($params['filter'], true);
            if(json_last_error() !== 0) {
                throw new RequestBodyException('Parameter [filter] must be valid json string.');
            }
        } else {
            $params['filter'] = '';
        }

        return $params;
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
        if($propertyList) {
            foreach ($propertyList as $propertyItem) {
                if(
                    ($propertyItem['type'] === 'S' && $propertyItem['user_type'] !== 'directory')
                    || ($propertyItem['type'] === 'N')
                    || ($propertyItem['type'] === 'F')
                ) {
                    $source[] = $propertyItem['code'];
                } else {
                    $source[] = $propertyItem['code'].'_*';
                }
            }
        }
        return $source;
    }


}
