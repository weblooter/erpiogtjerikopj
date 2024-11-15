<?php

namespace NaturaSiberica\Api\Repositories\Content;

use Bitrix\Main\ORM\Query\Query;
use NaturaSiberica\Api\DTO\Content\CertificateDTO;
use NaturaSiberica\Api\Traits\Entities\HighloadBlockTrait;

class CertificateRepository
{
    use HighloadBlockTrait;
    
    const HL_ENTITY_NAME = 'Certificates';
    
    protected Query $query;

    /**
     * @var CertificateDTO[]
     */
    protected array $collection = [];

    protected array $select = [
        'name' => 'UF_NAME',
        'description' => 'UF_DESCRIPTION',
        'image' => 'UF_IMAGE'
    ];
    
    public function __construct()
    {
        $this->setQuery();
    }

    private function setQuery()
    {
        $this->query = $this->getEntity()::query()->setSelect($this->select);
    }

    public function getEntity()
    {
        return $this->getHlEntityByEntityName(self::HL_ENTITY_NAME)->getDataClass();
    }

    private function prepareCollection(): CertificateRepository
    {
        $items = $this->query->fetchAll();

        if (!empty($items)) {
            foreach ($items as &$item) {
                $item['image'] = \CFile::GetPath($item['image']);
                $this->collection[] = new CertificateDTO($item);
            }
        }

        return $this;
    }

    /**
     * @return CertificateDTO[]
     */
    public function all(): array
    {
        $this->prepareCollection();
        return $this->collection;
    }
}
