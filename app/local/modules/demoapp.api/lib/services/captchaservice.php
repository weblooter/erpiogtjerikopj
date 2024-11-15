<?php

namespace NaturaSiberica\Api\Services;

use NaturaSiberica\Api\Tools\Settings\Options;
use ReCaptcha\ReCaptcha;

class CaptchaService
{
    private string $secretKey;
    private string $chanel;

    public function setChanel(string $userAgent): CaptchaService
    {
        if(mb_stripos($userAgent, 'dart') !== false) {
            $this->chanel = 'mobile';
        } else {
            $this->chanel = 'desktop';
        }

        return $this;
    }

    public function validateCaptcha(array $data)
    {
        $methodName = $this->chanel.'CaptchaValidate';
        if(method_exists($this, $methodName)) {
            $this->setSecretKey($data);
            $this->$methodName($data);
        }
    }

    protected function setSecretKey(array $data)
    {
        if($this->chanel === 'mobile') {
            $hash = hash_hmac('sha256', $data['phone'], 'A99ED5533F67E8AEFEBC8F5AFA7A5', true);
            $this->secretKey = base64_encode($hash);
        } else {
            $this->secretKey = Options::getReCaptchaSecretKey();
        }
    }

    protected function mobileCaptchaValidate(array $data)
    {
        if($this->secretKey !== $data['captcha']) {
            throw new \Exception('Невалидная капча', 422);
        }
    }

    protected function desktopCaptchaValidate(array $data)
    {
        $recaptcha = new ReCaptcha(Options::getReCaptchaSecretKey());
        $resp = $recaptcha->setExpectedHostname(Options::getReCaptchaHostname())->verify($data['captcha']);
        if (!$resp->isSuccess() || !empty($resp->getErrorCodes())) {
            throw new \Exception('Невалидная капча', 422);
        }
    }

}
