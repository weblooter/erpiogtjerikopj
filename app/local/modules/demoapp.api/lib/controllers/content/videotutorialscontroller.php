<?php

namespace NaturaSiberica\Api\Controllers\Content;

use NaturaSiberica\Api\Services\Content\VideoTutorialService;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class VideoTutorialsController
{
    use ResponseResultTrait;

    private VideoTutorialService $service;

    public function __construct()
    {
        $this->service = new VideoTutorialService();
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @param array                  $args
     *
     * @return ResponseInterface
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->service, 'getTutorials'],
            [$args['code']],
            $request
        );
    }
}
