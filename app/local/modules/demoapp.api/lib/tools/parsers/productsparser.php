<?php

namespace NaturaSiberica\Api\Tools\Parsers;

use Bitrix\Catalog\Model\Price;
use Bitrix\Catalog\Model\Product;
use Bitrix\Catalog\ProductTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use CCatalogGroup;
use CUtil;
use NaturaSiberica\Api\Interfaces\Repositories\RepositoryInterface;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;

/**
 * к торговым предложениям:
 * - объём - числовое значение + мера измерения
 */
class ProductsParser
{
    use ResponseResultTrait;
    use InfoBlockTrait;

    const DEFAULT_PRICE = 500;
    const TOTAL_ELEMENTS_ADD_PER_QUERY = 50;

    const IBLOCK_CODE_PRODUCTS = 'products';
    const IBLOCK_CODE_OFFERS = 'offers';

    private \CIBlockSection $section;
    private \CIBlockElement $element;

    private int $one = 1;
    private int $columns = 0;
    private int $rows = 0;

    private ?int $categoryId = null;
    private ?int $subCategoryId = null;

    private ?int $brandId = null;

    private array $raw = [];
    private array $productItems = [];
    private array $offersItems = [];
    private array $csvKeys = [];
    private array $csvValues = [];
    private array $errors = [];

    public function __construct()
    {
        $this->section = new \CIBlockSection();
        $this->element = new \CIBlockElement();
    }

    /**
     * @return array
     */
    public function getRaw(): array
    {
        return $this->raw;
    }

    /**
     * @return array
     */
    public function getProductItems(): array
    {
        return $this->productItems;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array
     */
    public function getCsvKeys(): array
    {
        return $this->csvKeys;
    }

    public function getProductsIblockId(): int
    {
        return $this->getIblockId(self::IBLOCK_CODE_PRODUCTS);
    }

    public function getOffersIblockId(): int
    {
        return $this->getIblockId(self::IBLOCK_CODE_OFFERS);
    }

    /**
     * @param string $csvPath
     *
     * @return $this
     *
     * @throws FileNotFoundException
     */
    public function parseCsv(string $csvPath): ProductsParser
    {
        if (!File::isFileExists($csvPath)) {
            throw new FileNotFoundException($csvPath);
        }
        $this->csv = fopen($csvPath, 'r');

        $totalSymbols = 1000000;
        $row = 1;

        if (($csv = fopen($csvPath, 'r')) !== false) {

            while (($data = fgetcsv($csv, $totalSymbols, ',')) !== false) {
                $this->columns = count($data);
                if ($row === RepositoryInterface::COUNT_ONE) {
                    $this->csvKeys = $data;
                } elseif ($row > RepositoryInterface::COUNT_ONE) {
                    $this->csvValues = $data;
                    $this->raw[] = array_combine($this->csvKeys, $this->csvValues);
                }
                $row++;
                usleep(100);
            }
            fclose($this->csv);
        }

        $this->rows = count($this->raw);

        return $this;
    }

    /**
     * @param string $key
     * @param string $iblockCode
     *
     * @return string
     */
    public function extractFieldFromKey(string $key, string $iblockCode): string
    {
        return strtoupper(
            str_ireplace(
                $this->getPrefix($iblockCode), '', $key
            )
        );
    }

    private function getPrefix(string $code): string
    {
        return sprintf('%s_', $code);
    }

    public function getProperties(int $iblockId): array
    {
        $properties = [];
        $runtime = [
            'ENUM' => [
                'data_type' => PropertyEnumerationTable::class,
                'reference' => [
                    'this.ID' => 'ref.PROPERTY_ID'
                ]
            ],
            'SECTION' => [
                'data_type' => SectionTable::class,
                'reference' => [
                    'this.LINK_IBLOCK_ID' => 'ref.IBLOCK_ID'
                ]
            ],
            'ELEMENT' => [
                'data_type' => ElementTable::class,
                'reference' => [
                    'this.LINK_IBLOCK_ID' => 'ref.IBLOCK_ID'
                ]
            ]
        ];

        $select = [
            'VALUE_ID' => 'ENUM.ID',
            'VALUE' => 'ENUM.VALUE',
            'SECTION_ID' => 'SECTION.ID',
            'SECTION_NAME' => 'SECTION.NAME',
            'ELEMENT_ID' => 'ELEMENT.ID',
            'ELEMENT_NAME' => 'ELEMENT.NAME'
        ];

        $rows = $this->getIblockPropertyList($iblockId, [], $select, $runtime);

        foreach ($rows as $row) {
            $code = $row['CODE'];


            $property = [
                'id' => (int) $row['ID'],
                'code' => $code,
                'type' => $row['PROPERTY_TYPE'],
                'required' => $row['IS_REQUIRED'],
                'multiple' => $row['MULTIPLE']
            ];

            if ((int) $row['LINK_IBLOCK_ID'] > 0) {
                $property['link_iblock_id'] = (int) $row['LINK_IBLOCK_ID'];
            }

            switch ($row['PROPERTY_TYPE']) {
                case PropertyTable::TYPE_LIST:
                    $valueId = $row['VALUE_ID'];
                    $valueName = $row['VALUE'];
                    $property['list_values'][$valueId] = $valueName;
                    break;
                case PropertyTable::TYPE_SECTION:
                    $sectionId = $row['SECTION_ID'];
                    $sectionName = $row['SECTION_NAME'];
                    $property['list_values'][$sectionId] = $sectionName;
                    break;
                case PropertyTable::TYPE_ELEMENT:
                    $productElementId = $row['ELEMENT_ID'];
                    $elementName = $row['ELEMENT_NAME'];
                    $property['list_values'][$productElementId] = $elementName;
                    break;
            }

            $properties[$code] = $property;
        }

        return $properties;
    }

    protected function getOptions(int $iblockId, string $iblockCode)
    {
        return [
            'iblock_id' => $iblockId,
            'iblock_code' => $iblockCode,
            'iblock_properties' => $this->getProperties($iblockId)
        ];
    }

    protected function prepareElementProductFields(array $data, array $options)
    {
        $iblockId = $options['iblock_id'];
        $fields = [
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => 'Y',
            'ACTIVE_FROM' => DateTime::createFromTimestamp(time()),
            'PROPERTY_VALUES' => []
        ];

        $properties = $options['iblock_properties'];

        foreach ($data as $key => $value) {

            if (stripos($key, $options['iblock_code']) === false) {
                continue;
            }

            $field = $this->extractFieldFromKey($key, $options['iblock_code']);

            switch ($field) {
                case 'NAME':
                    $fields[$field] = $value;
                    $fields['CODE'] = CUtil::translit($value, LANGUAGE_ID);
                    break;
                case 'CATEGORY':
                    $this->categoryId = $this->findSectionPropertyByValue($value, $iblockId) ?? $this->addSection($iblockId, $value);
                    $fields['IBLOCK_SECTION_ID'] = $this->categoryId;
                    break;
                case 'SUBCATEGORY':
                    $this->subCategoryId = $this->findSectionPropertyByValue($value, $iblockId) ?? $this->addSection($iblockId, $value, $this->categoryId);

                    $fields['IBLOCK_SECTION_ID'] = $this->subCategoryId;
                    break;
                case 'BRAND':
                    $linkIblockId = $properties[$field]['link_iblock_id'];
                    $this->brandId = $this->findSectionPropertyByValue($value, $linkIblockId) ?? $this->addSection($linkIblockId, $value);
                    $fields['PROPERTY_VALUES'][$field] = $this->brandId;
                    break;
                case 'SERIES':
                    $linkIblockId = $properties[$field]['link_iblock_id'];
                    $seriesId = $this->findElementPropertyByValue($value, $linkIblockId) ?? $this->addElement($linkIblockId, $value, $this->brandId);
                    $fields['PROPERTY_VALUES'][$field] = $seriesId;
                    break;
                case 'NEW':
                    if ($value !== 'Новинки') {
                        break;
                    }
                    $fields['PROPERTY_VALUES'][$field] = $this->findEnumPropertyByValue('Y', $iblockId);
                    break;
                default:
                    if (array_key_exists($field, $properties) && empty($fields['PROPERTY_VALUES'][$field])) {
                        switch ($properties[$field]['type']) {
                            case PropertyTable::TYPE_LIST:
                                $fields['PROPERTY_VALUES'][$field] = $this->findEnumPropertyByValue($value, $iblockId);
                                break;
                            default:
                                $fields['PROPERTY_VALUES'][$field] = $value;
                        }
                    } else {
                        $fields[$field] = $value;
                    }
                    break;
            }
        }

        return $fields;
    }

    public function import()
    {
        echo 'start products items formatting...' . PHP_EOL;
        $productOptions = $this->getOptions($this->getIblockId(self::IBLOCK_CODE_PRODUCTS), self::IBLOCK_CODE_PRODUCTS);
        $offersOptions = $this->getOptions($this->getIblockId(self::IBLOCK_CODE_OFFERS), self::IBLOCK_CODE_OFFERS);

        for ($i = 0; $i < ceil($this->rows/self::TOTAL_ELEMENTS_ADD_PER_QUERY); $i++) {

            $data = array_slice($this->raw, 0, self::TOTAL_ELEMENTS_ADD_PER_QUERY);

            foreach ($data as &$item) {
                $productFields = $this->prepareElementProductFields($item, $productOptions);
                $productId = $this->getElementId($productOptions['iblock_id'], $productFields['CODE']);

                if ($productId) {
                    $this->element->Update($productId, $productFields);
                    \CIBlockElement::SetPropertyValuesEx($productId, $productOptions['iblock_id'], $productFields['PROPERTY_VALUES']);
                    continue;
                }

                $productId = $this->element->Add($productFields);

                if ((int) $productId > 0) {
                    $this->productItems[$productId]['element_id'] = (int) $productId;
                    $offerFields = $this->prepareElementOffersFields($productId, $item, $offersOptions);
                    $offerId = $this->getElementId($offersOptions['iblock_id'], $offerFields['CODE']);
                    
                    if ($offerId) {
                        $this->element->Update($offerId, $offerFields);
                        \CIBlockElement::SetPropertyValuesEx($offerId, $offersOptions['iblock_id'], $offerFields['PROPERTY_VALUES']);
                        continue;
                    } else {

                    }
                    
                    $offerId = $this->element->Add($offerFields);

                    if (!empty($this->element->LAST_ERROR)) {
                        $this->errors['offers_errors'][$productId] = $this->element->LAST_ERROR;
                    } else {
                        $this->productItems[$productId]['offer_id'] = (int) $offerId;
                    }

                    $addProduct = Product::add([
                        'ID' => $productId,
                        'AVALIABLE' => 'Y',
                        'TYPE' => ProductTable::TYPE_SKU
                    ]);

                    if ($addProduct->isSuccess()) {

                        $this->productItems[$productId]['product_id'] = $addProduct->getId();
                        $addPrice = Price::add([
                            'CURRENCY' => 'RUB',
                            'PRODUCT_ID' => $offerId,
                            'CATALOG_GROUP_ID' => CCatalogGroup::GetBaseGroupId(),
                            'PRICE' => self::DEFAULT_PRICE
                        ]);

                        if ($addPrice->isSuccess()) {
                            $this->productItems[$productId]['price_id'] = $addPrice->getId();
                        } else {
                            $this->errors['price_errors'][$productId] = $addPrice->getErrorMessages();
                        }

                    } else {
                        $this->errors['catalog_product_errors'][$productId] = $addProduct->getErrorMessages();
                    }
                } else {
                    $this->errors['products'][$productFields['CODE']] = $this->element->LAST_ERROR;
                }
            }

            usleep(500);
        }

        if (!empty($this->errors)) {
            \CEventLog::Add([
                'SEVERITY' => 'ERROR',
                'AUDIT_TYPE_ID' => 'PRODUCTS_PARSING_ERROR',
                'DESCRIPTION' => json_encode($this->errors, JSON_UNESCAPED_UNICODE)
            ]);
        }

        echo sprintf('product items prepared%s', PHP_EOL);

        return $this;
    }

    protected function prepareElementOffersFields(int $productId, array $data, array $options)
    {
        $iblockId = $options['iblock_id'];

        $fields = [
            'IBLOCK_ID' => $iblockId,
            'NAME' => $data['products_name'],
            'CODE' => CUtil::translit($data['products_name'], LANGUAGE_ID),
            'ACTIVE' => 'Y',
            'ACTIVE_FROM' => DateTime::createFromTimestamp(time()),
            'PROPERTY_VALUES' => [
                'CML2_LINK' => $productId,
                'NUMERIC_VALUE' => $data['offers_numeric_value'],
                'UNIT' => $this->findEnumPropertyByValue($data['offers_unit'], $iblockId)
            ],
        ];

        return $fields;
    }

    /**
     * Возвращает ID раздела инфоблока по названию или создаёт новый
     *
     * @param string   $value
     * @param int      $iblockId
     * @param int|null $parentSection
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function findSectionPropertyByValue(string $value, int $iblockId, int $parentSection = null)
    {
        $object = SectionTable::getList([
            'filter' => [
                'NAME' => $value,
                'IBLOCK_ID' => $iblockId
            ],
            'select' => ['ID', 'NAME', 'IBLOCK_ID']
        ])->fetchObject();

        return $object !== null ? (int) $object->getId() : $this->addSection($iblockId, $value, $parentSection);

    }

    /**
     * Возвращает ID элемента инфоблока по названию или создаёт новый
     *
     * @param string   $value
     * @param int      $iblockId
     * @param int|null $sectionId
     *
     * @return int|string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function findElementPropertyByValue(string $value, int $iblockId, int $sectionId = null)
    {
        $object = ElementTable::getList([
            'filter' => [
                'IBLOCK_ID' => $iblockId,
                'NAME' => $value
            ],
            'select' => ['ID', 'NAME']
        ])->fetchObject();

        return $object !== null ? (int) $object->getId(): $this->addElement($iblockId, $value, $sectionId);
    }

    /**
     * Возвращает ID значения списочного свойства инфоблока
     *
     * @param string $value
     * @param int    $iblockId
     *
     * @return int|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public function findEnumPropertyByValue(string $value, int $iblockId)
    {
        $enum = PropertyEnumerationTable::getList([
            'filter' => [
                'IBLOCK_ID' => $iblockId,
                'VALUE' => $value
            ],
            'select' => [
                'ID', 'IBLOCK_ID' => 'P.IBLOCK_ID'
            ],
            'runtime' => [
                new Reference('P', PropertyTable::class, Join::on('this.PROPERTY_ID', 'ref.ID'))
            ]
        ])->fetchObject();

        return $enum !== null ? (int) $enum->getId() : null;
    }

    /**
     * Добавляет новый раздел инфоблока
     *
     *
     * @param int      $iblockId
     * @param string   $name
     * @param int|null $parentSectionId
     *
     * @return int|string
     */
    public function addSection(int $iblockId, string $name, int $parentSectionId = null)
    {
        $fields = [
            'IBLOCK_ID' => $iblockId,
            'NAME' => $name,
            'CODE' => CUtil::translit($name, LANGUAGE_ID),
            'IBLOCK_SECTION_ID' => $parentSectionId
        ];

        $id = $this->section->Add($fields);

        return (int) $id > 0 ? (int) $id : $this->section->LAST_ERROR;
    }

    /**
     * Добавляет новый элемент инфоблока
     *
     * @param int      $iblockId
     * @param string   $name
     * @param int|null $sectionId
     *
     * @return int|string
     */
    public function addElement(int $iblockId, string $name, int $sectionId = null)
    {
        $fields = [
            'IBLOCK_ID' => $iblockId,
            'NAME' => $name,
            'CODE' => CUtil::translit($name, LANGUAGE_ID),
            'IBLOCK_SECTION_ID' => $sectionId
        ];

        $id = $this->element->Add($fields);

        return (int) $id > 0 ? (int) $id : $this->element->LAST_ERROR;
    }
}
