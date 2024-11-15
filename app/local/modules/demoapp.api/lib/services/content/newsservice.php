<?php

namespace NaturaSiberica\Api\Services\Content;

use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\Interfaces\ConstantEntityInterface;
use NaturaSiberica\Api\Repositories\Content\NewsRepository;

class NewsService
{
    private NewsRepository $repository;
    
    public function __construct()
    {
        $this->repository = new NewsRepository();
    }

    /**
     * Получает список элементов
     *
     * @param array $params
     * @param array $list
     *
     * @return array
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function index(array $params, array $list = []): array
    {
        return [
            'pagination' => [
                'limit'  => (int) ($params['limit'] ? : ConstantEntityInterface::DEFAULT_NEWS_COUNT),
                'offset' => (int) ($params['offset'] ? : ConstantEntityInterface::MIN_OFFSET_VALUE),
                'total'  => (int) ($this->repository->count() ?: 0),
            ],
            'list' => $this->repository->all($params['limit'] ?: 0, $params['offset'] ?: 0)
        ];
    }

    /**
     * Получает информацию об одном элементе
     *
     * @param string $code
     * @param array  $params
     *
     * @return array
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function get(string $code, array $params): array
    {
        return [
            'news' => $this->repository->findByCode($code)->get()
        ];
    }
}
