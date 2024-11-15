<?php

namespace NaturaSiberica\Api\Helpers\Http;

use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Request;

class RequestHelper
{
    /**
     * @return HttpRequest|Request
     */
    public static function getRequest()
    {
        return Context::getCurrent()->getRequest();
    }

    /**
     * Возвращает схему URL
     * @return string
     */
    public static function getHttpScheme(): string
    {
        return self::getRequest()->isHttps() ? 'https://' : 'http://';
    }
}
