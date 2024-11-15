<?php

namespace NaturaSiberica\Api\Controllers\Content;

use NaturaSiberica\Api\Interfaces\Controllers\Content\CertificateControllerInterface;
use NaturaSiberica\Api\Interfaces\Services\Content\CertificateServiceInterface;
use NaturaSiberica\Api\Services\Content\CertificateService;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CertificateController implements CertificateControllerInterface
{
    use ResponseResultTrait;

    private CertificateServiceInterface $certificateService;

    public function __construct()
    {
        $this->certificateService = new CertificateService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->certificateService, 'getCertificates'],
            [],
            $request
        );
    }
}
