<?php

namespace NaturaSiberica\Api\Services\Structure;

use NaturaSiberica\Api\Repositories\Structure\Menu\FooterMenuRepository;
use NaturaSiberica\Api\Repositories\Structure\Menu\HeaderMenuRepository;

class MenuService
{
    private HeaderMenuRepository $headerMenuRepository;
    private FooterMenuRepository $footerMenuRepository;

    public function __construct()
    {
        $this->headerMenuRepository = new HeaderMenuRepository();
        $this->footerMenuRepository = new FooterMenuRepository();
    }

    public function getHeaderMenu()
    {
        return [
            'list' => $this->headerMenuRepository->all()
        ];
    }

    public function getFooterMenu()
    {
        return [
            'list' => $this->footerMenuRepository->getCollection()->toArray()
        ];
    }
}
