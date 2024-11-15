<?php

namespace NaturaSiberica\Api\Repositories\Content\Pages;

use Bitrix\Iblock;
use Bitrix\Main\FileTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM;
use Bitrix\Main\SystemException;
use Exception;
use Fig\Http\Message\StatusCodeInterface;
use NaturaSiberica\Api\DTO\Content\Pages\PageDTO;
use NaturaSiberica\Api\DTO\Content\Pages\PageItemDTO;
use NaturaSiberica\Api\DTO\Content\Pages\PageSeoDTO;
use NaturaSiberica\Api\Factories\Content\Pages\PageDTOFactory;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Interfaces\Repositories\Content\Pages\PageRepositoryInterface;
use NaturaSiberica\Api\Traits\Entities\InfoBlockTrait;
use NaturaSiberica\Api\Traits\NormalizerTrait;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ServerRequestFactory;

Loader::includeModule('iblock');
Loc::loadMessages(__FILE__);

class PageRepository implements PageRepositoryInterface
{
    use NormalizerTrait;

    /**
     * @var PageDTO[]
     */
    protected array $collection = [];

    protected ?array $iblockSection = null;
    protected string $iblockCode = ConstantEntityInterface::IBLOCK_PAGES;

    protected array           $select = [
        'ID',
        'NAME',
        'CODE',
        'SORT',
        'DETAIL_PICTURE',
        'CONTENT'   => 'DETAIL_TEXT',
        'SUBDIR'    => 'FILE.SUBDIR',
        'FILE_NAME' => 'FILE.FILE_NAME',
    ];

    protected array $order = ['SORT' => 'ASC'];

    protected ORM\Query\Query $query;

    public function __construct()
    {
        $this->setQuery();
    }

    public function setIblockCode(?string $lang = ''): PageRepository
    {
        if($lang && $lang !== 'ru') {
            $data = Iblock\IblockTable::getList([
                'filter' => ['CODE' => $this->iblockCode.'_'.$lang],
                'select' => ['ID', 'API_CODE']
            ])->fetchObject();
            if(!$data || !$data->getId()) {
                throw new Exception('Данных для данной языковой версии нет.', StatusCodeInterface::STATUS_BAD_REQUEST);
            }
            $this->iblockCode = $data->get('API_CODE');
        }

        return $this;
    }

    private function setQuery()
    {
        $this->query = $this->getEntity()::query();

        $filter = [
            'IBLOCK_ID' => $this->getIblockId(),
            'ACTIVE' => 'Y'
        ];

        $this->query->setFilter($filter);
        $this->query->setOrder($this->order);
        $this->query->setSelect($this->select);

        $this->query->registerRuntimeField(
            new ORM\Fields\Relations\Reference(
                'FILE', FileTable::class, ORM\Query\Join::on('this.DETAIL_PICTURE', 'ref.ID')
            )
        );

        return $this;
    }

    /**
     * @return ORM\Query\Query
     */
    public function getQuery(): ORM\Query\Query
    {
        return $this->query;
    }

    /**
     * @return array|null
     */
    public function getIblockSection(): ?array
    {
        return $this->iblockSection;
    }

    /**
     * @param array|null $iblockSection
     *
     * @return PageRepository
     */
    public function setIblockSection(?array $iblockSection): PageRepository
    {
        $this->iblockSection = $iblockSection;
        return $this;
    }

    private function compileEntity()
    {
        return Iblock\IblockTable::compileEntity($this->iblockCode);
    }

    private function getEntity()
    {
        return $this->compileEntity()->getDataClass();
    }

    public function getIblockId()
    {
        return $this->compileEntity()->getIblock()->getId();
    }

    /**
     * @param string $code
     *
     * @return PageDTO|PageItemDTO
     *
     * @throws Exception
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function findByCode(string $code)
    {
        $this->findIblockSection($code);

        if (empty($this->iblockSection)) {
            $this->query->addFilter('CODE', $code);
        } else {
            $this->query->addFilter('IBLOCK_SECTION_ID', $this->iblockSection['ID']);
        }

        return PageDTOFactory::create($this);
    }

    /**
     * @param string $class
     * @param int    $id
     *
     * @return array|void
     */
    public function prepareSeo(string $class, int $id)
    {
        /**
         * @var Iblock\InheritedProperty\BaseValues $valuesObject
         */
        $valuesObject = new $class($this->getIblockId(), $id);

        $values = $valuesObject->getValues();

        if (empty($values)) {
            return;
        }

        $seo = [];

        foreach ($values as $field => $value) {
            $key = $this->convertSnakeToCamel(str_ireplace(['SECTION_', 'ELEMENT_'], '', $field), true, true);

            if (! property_exists(PageSeoDTO::class, $key)) {
                continue;
            }

            $seo[$key] = $value;
        }

        return $seo;
    }

    public function findIblockSection(string $code): PageRepository
    {
        $section = Iblock\Model\Section::compileEntityByIblock($this->getIblockId())::getList([
            'filter'  => [
                '=CODE'      => $code,
            ],
            'select'  => [
                'ID',
                'NAME',
                'PICTURE',
                'CODE',
                'DESCRIPTION',
                'UF_MP_WHITE_HEADER',
                'SUBDIR'    => 'FILE.SUBDIR',
                'FILE_NAME' => 'FILE.FILE_NAME',
            ],
            'runtime' => [
                new ORM\Fields\Relations\Reference(
                    'FILE', FileTable::class, ORM\Query\Join::on('this.PICTURE', 'ref.ID')
                ),
            ],
        ])->fetch();

        if (! empty($section)) {
            $this->setIblockSection($section);
        }

        return $this;
    }
}
