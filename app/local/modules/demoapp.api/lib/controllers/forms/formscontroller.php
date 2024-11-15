<?php

namespace NaturaSiberica\Api\Controllers\Forms;

use NaturaSiberica\Api\Repositories\User\UserRepository;
use NaturaSiberica\Api\Services\CaptchaService;
use NaturaSiberica\Api\Services\Forms\FormsService;
use NaturaSiberica\Api\Tools\Settings\Options;
use NaturaSiberica\Api\Traits\Http\RequestServiceTrait;
use NaturaSiberica\Api\Traits\Http\ResponseResultTrait;
use NaturaSiberica\Api\Validators\User\UserValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class FormsController
{
    use RequestServiceTrait, ResponseResultTrait;

    private FormsService $service;
    private UserRepository $user;
    private UserValidator $userValidator;

    public function __construct()
    {
        $this->service = new FormsService();
        $this->user = new UserRepository();
        $this->userValidator = new UserValidator($this->user);
    }

    public function feedback(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->userValidator::validateFuser($request->getAttribute('fuserId'));

        if($userId = $request->getAttribute('userId')) {
            $userDTO = $this->user->findById($userId)->get();
            $this->userValidator->validateUnRegisteredUser($userDTO);
            $this->userValidator->validateBlockedUser($userDTO->id);
        }

        $body = $this->parseRequestBody($request);
        $body['userAgent'] = current($request->getHeader('User-Agent'));
        if(Options::getReCaptchaNeed() !== 'Y') {
            $captcha = new CaptchaService();
            $captcha->setChanel($body['userAgent'])->validateCaptcha($body);
        }

        return $this->prepareResponse(
            $response,
            [$this->service, 'sendFeedback'],
            [$body, $request->getAttribute('userId')]
        );
    }
}
