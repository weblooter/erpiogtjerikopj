<?php

namespace NaturaSiberica\Api\Controllers\User;

use NaturaSiberica\Api\Interfaces\Services\User\PhoneServiceInterface;
use NaturaSiberica\Api\Interfaces\Controllers\User\PhoneControllerInterface;
use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Services\CaptchaService;
use NaturaSiberica\Api\Services\NotificationService;
use NaturaSiberica\Api\Services\User\PhoneService;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use NaturaSiberica\Api\Tools\Settings\Options;

class PhoneController implements PhoneControllerInterface
{
    use RequestServiceTrait, ResponseResultTrait;

    private PhoneServiceInterface $phoneService;

    public function __construct()
    {
        $this->phoneService = new PhoneService(new UserRepository());
    }

    /**
     * @inheritDoc
     */
    public function confirm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse(
            $response,
            [$this->phoneService, 'confirm'],
            [$this->parseRequestBody($request), $request->getAttribute('fuserId'), $request->getAttribute('userId')]
        );
    }

    public function getSiteKey(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->prepareResponse($response,[$this->phoneService, 'getCaptchaKey']);
    }

    /**
     * @inheritDoc
     */
    public function getCode(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->parseRequestBody($request);
        $body['userAgent'] = current($request->getHeader('User-Agent'));

        if(!$body['phone'] || !$body['captcha']) {
            throw new \Exception('Не верные данные', 400);
        }

        if(Options::getReCaptchaNeed() !== 'Y') {
            $captcha = new CaptchaService();
            $captcha->setChanel($body['userAgent'])->validateCaptcha($body);
        }

        return $this->prepareResponse(
            $response,
            [$this->phoneService, 'generateCode'],
            [$body, $request->getAttribute('fuserId'), $request->getAttribute('userId')]
        );
    }

    public function sendPush(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->parseRequestBody($request);
        $body['userAgent'] = current($request->getHeader('User-Agent'));

        return $this->prepareResponse(
            $response,
            [$this->phoneService, 'sendNotification'],
            [$body]
        );
    }
}
