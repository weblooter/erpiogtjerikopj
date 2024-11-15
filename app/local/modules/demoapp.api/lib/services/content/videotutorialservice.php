<?php

namespace NaturaSiberica\Api\Services\Content;

use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use NaturaSiberica\Api\DTO\Content\VideoTutorialsDTO;
use NaturaSiberica\Api\Repositories\Content\VideotutorialsRepository;

class VideoTutorialService
{
    private VideotutorialsRepository $repository;
    
    public function __construct()
    {
        $this->repository = new VideotutorialsRepository();
    }

    /**
     * @param string|null $code
     *
     * @return array|VideoTutorialsDTO[]
     *
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getTutorials(string $code = null): array
    {
        if ($code !== null) {
            return [
                'video' => $this->repository->findByCode($code)->get()
            ];
        }

        return [
            'list' => $this->repository->all()
        ];
    }
}
