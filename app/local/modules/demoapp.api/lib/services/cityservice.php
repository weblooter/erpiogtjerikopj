<?php

namespace NaturaSiberica\Api\Services;

use NaturaSiberica\Api\Repositories\CityRepository;

class CityService
{
    private CityRepository $cityRepository;

    public function __construct()
    {
        $this->cityRepository = new CityRepository();
    }

    public function getCities(): array
    {
        return [
            'list' => $this->cityRepository->all()
        ];
    }
}
