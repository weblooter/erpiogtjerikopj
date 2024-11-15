<?php

namespace NaturaSiberica\Api\Services\Content;

use NaturaSiberica\Api\Interfaces\Services\Content\CertificateServiceInterface;
use NaturaSiberica\Api\Repositories\Content\CertificateRepository;

class CertificateService implements CertificateServiceInterface
{
    private CertificateRepository $certificateRepository;

    public function __construct()
    {
        $this->certificateRepository = new CertificateRepository();
    }

    public function getCertificates(): array
    {
        return [
            'list' => $this->certificateRepository->all()
        ];
    }
}
