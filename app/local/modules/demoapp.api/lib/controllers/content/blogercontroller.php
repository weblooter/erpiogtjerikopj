<?php

namespace NaturaSiberica\Api\Controllers\Content;

use NaturaSiberica\Api\Interfaces\Controllers\Content\BlogerControllerInterface;
use NaturaSiberica\Api\Services\Content\BlogerService;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BlogerController implements BlogerControllerInterface
{
    use ResponseResultTrait;

    private BlogerService $blogerService;

    public function __construct()
    {
        $this->blogerService = new BlogerService();
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->blogerService, 'getBlogers'],
            [],
            $request
        );
    }
}
