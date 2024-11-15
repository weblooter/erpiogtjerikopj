<?php

namespace NaturaSiberica\Api\Traits\Http;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

trait RequestServiceTrait
{
    /**
     * @param ServerRequestInterface $request
     * @param bool                   $associative
     *
     * @return array|object|null
     */
    public function parseRequestBody(ServerRequestInterface $request, bool $associative = true): ?array
    {
        return json_decode((string)$request->getBody(), $associative);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $requiredParameters
     *
     * @return bool
     *
     */
    protected function checkRequiredParameters(ServerRequestInterface $request, array $requiredParameters): bool
    {
        foreach ($requiredParameters as $parameter) {
            if (!$this->checkRequiredParameter($parameter, $this->parseRequestBody($request))) {
                throw new HttpBadRequestException($request, sprintf('Parameter %s not found in request body', $parameter));
            }
        }

        return true;
    }

    protected function checkRequiredParameter(string $parameter, array $requestBody): bool
    {
        return array_key_exists($parameter, $requestBody);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array                  $requiredParameters
     *
     * @return array|bool
     */
    public function assertRequestBody(ServerRequestInterface $request, array $requiredParameters)
    {
        try {
            return $this->checkRequiredParameters($request, $requiredParameters);
        } catch (HttpBadRequestException $e) {
            return [
                'code' => StatusCodeInterface::STATUS_BAD_REQUEST,
                'type' => 'bad_request',
                'message' => $e->getMessage()
            ];
        }
    }
}
