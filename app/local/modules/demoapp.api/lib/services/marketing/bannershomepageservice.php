<?php

namespace NaturaSiberica\Api\Services\Marketing;

use Bitrix\Main\ArgumentNullException;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Marketing\BannerHomepageRepository;
use NaturaSiberica\Api\Tools\Settings\Options;

class BannersHomepageService
{
    private BannerHomepageRepository $repository;

    public function __construct()
    {
        $this->repository = new BannerHomepageRepository(ConstantEntityInterface::IBLOCK_BANNER_HOMEPAGE);
    }

    /**
     * @throws ArgumentNullException
     */
    public function index(): array
    {
        return [
            'banners' => $this->repository->findAll(),
            'texts' => [
                'textarea_before_brands' => Options::getTextareaBeforeBrands(),
                'textarea_before_bloggers_list' => Options::getTextareaBeforeBloggersList(),
                'selections_block_title' => Options::getSelectionsBlockTitle(),
                'subscription_block_title' => Options::getSubscriptionBlockTitle()
            ],
            'seo' => [
                'title' => trim(Options::getSeoTitle()),
                'pageTitle' => trim(Options::getSeoPageTitle()),
                'description' => trim(Options::getSeoDescription()),
                'keywords' => trim(Options::getSeoKeywords()),
            ]
        ];
    }

    public function get(string $code): array
    {
        return $this->repository->findByPosition($code);
    }
}
