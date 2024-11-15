<?php

namespace NaturaSiberica\Api\Services\Content;

use NaturaSiberica\Api\Interfaces\Services\Content\BlogerServiceInterface;
use NaturaSiberica\Api\Repositories\Content\BlogerRepository;

class BlogerService implements BlogerServiceInterface
{
    public function getBlogers(): array
    {
        $blogerRepository = new BlogerRepository();
        return [
            'list' => $blogerRepository->all()
        ];
    }
}
