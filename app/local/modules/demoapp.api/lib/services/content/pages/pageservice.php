<?php

namespace NaturaSiberica\Api\Services\Content\Pages;

use NaturaSiberica\Api\Repositories\Content\Pages\PageRepository;

class PageService
{
    private PageRepository $pageRepository;

    public function __construct()
    {
        $this->pageRepository = new PageRepository();
    }

    public function getPage(string $code, array $params = [])
    {
        return $this->pageRepository->setIblockCode($params['lang'])->findByCode($code)->toArray();
    }
}
