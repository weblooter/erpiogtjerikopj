<?php

namespace NaturaSiberica\Api\Interfaces\Repositories\Content\Pages;

use Bitrix\Main\ORM\Query\Query;

interface PageRepositoryInterface
{
    /**
     * @param string $code
     */
    public function findIblockSection(string $code);

    public function getIblockSection(): ?array;

    public function getQuery(): Query;

    public function findByCode(string $code);

    public function prepareSeo(string $class, int $id);
}
